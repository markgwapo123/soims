<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryLot;
use App\Models\InventoryTransaction;
use App\Models\Ris;
use App\Models\RisItem;
use App\Models\SoimsNotification;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RisService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /** Confirmed lines with the stock available right now; the RIS quantity is the confirmed (final) quantity. */
    public function lines(SupplyRequest $request): Collection
    {
        $request->loadMissing(['items.item', 'items.confirmedItem']);
        $lines = $request->items->filter(fn ($line) => (float) $line->confirmed_quantity > 0);
        $itemIds = $lines->map(fn ($line) => $line->confirmed_item_id ?? $line->item_id)->unique()->all();
        $inventories = Inventory::whereIn('item_id', $itemIds)->get()->keyBy('item_id');

        return $lines->map(function ($line) use ($inventories) {
            $item = $line->confirmedItem ?? $line->item;
            $inventory = $inventories[$item->id] ?? null;
            $requested = (float) $line->confirmed_quantity;
            $available = $inventory ? max(0.0, (float) $inventory->quantity_on_hand - (float) $inventory->quantity_reserved) : 0.0;

            return [
                'line' => $line, 'item' => $item, 'requested' => $requested,
                'available' => $available, 'max_issue' => min($requested, $available),
            ];
        })->values();
    }

    public function prepare(SupplyRequest $supplyRequest, User $issuer, array $input, ?string $ipAddress = null, ?string $userAgent = null): Ris
    {
        return DB::transaction(function () use ($supplyRequest, $issuer, $input, $ipAddress, $userAgent): Ris {
            $request = SupplyRequest::lockForUpdate()->findOrFail($supplyRequest->id);

            if ($request->status !== SupplyRequest::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['ris' => 'Only confirmed requests can be processed.']);
            }

            $request->load(['items.item', 'items.confirmedItem', 'approvals', 'department', 'office']);
            Inventory::whereIn('item_id', $request->items->map(fn ($l) => $l->confirmed_item_id ?? $l->item_id))->lockForUpdate()->get();
            $lines = $this->lines($request);

            $rows = [];
            foreach ($lines as $row) {
                $given = $input['issue'][$row['line']->id] ?? null;
                $issue = ($given === null || $given === '') ? $row['max_issue'] : round((float) $given, 3);

                if ($issue < 0 || $issue - $row['max_issue'] > 0.0005) {
                    throw ValidationException::withMessages([
                        'issue' => sprintf('Issue quantity for %s cannot exceed the requested or available quantity.', $row['item']->name),
                    ]);
                }

                $rows[] = $row + ['issue' => $issue];
            }

            if ($rows === [] || array_sum(array_column($rows, 'issue')) <= 0) {
                throw ValidationException::withMessages(['ris' => 'There is no available stock to issue for this request.']);
            }

            $ris = Ris::create([
                'ris_uuid' => (string) Str::uuid(),
                'ris_no' => (string) Str::uuid(),
                'revision_no' => 0,
                'supply_request_id' => $request->id,
                'entity_name' => config('soims.entity_name'),
                'fund_cluster' => $input['fund_cluster'] ?? null,
                'division' => $request->department?->name,
                'office' => $request->office?->name,
                'responsibility_center_code' => $input['responsibility_center_code'] ?? null,
                'purpose' => $request->purpose,
                'requested_by' => $request->requestor_id,
                'approved_by' => $request->approvals->where('decision', 'approved')->last()?->approver_id,
                'issued_by' => $issuer->id,
                'received_by' => $request->requestor_id,
                'received_printed_name' => $input['received_printed_name'] ?? $request->requestor->name,
                'received_designation' => $input['received_designation'] ?? null,
                'status' => Ris::STATUS_PREPARED,
            ]);
            $ris->update(['ris_no' => sprintf('RIS-%s-%06d', now()->format('Y'), $ris->id)]);

            $partial = false;
            foreach ($rows as $row) {
                $item = $row['item'];
                $line = $row['line'];
                $unitPrice = $line->confirmed_item_id
                    ? ($item->default_unit_price ?? 0)
                    : ($line->unit_price_snapshot ?? $item->default_unit_price ?? 0);
                $notAvailable = round($row['requested'] - $row['issue'], 3);
                $partial = $partial || $notAvailable > 0;

                RisItem::create([
                    'ris_id' => $ris->id,
                    'item_id' => $item->id,
                    'supply_request_item_id' => $line->id,
                    'stock_no_snapshot' => $item->stock_no,
                    'unit_snapshot' => $item->unit_of_measure,
                    'description_snapshot' => trim($item->name.' '.$item->description),
                    'requested_quantity' => $row['requested'],
                    'available_quantity' => $row['available'],
                    'issue_quantity' => $row['issue'],
                    'not_available_quantity' => $notAvailable,
                    'unit_price' => $unitPrice,
                    'amount' => round($row['issue'] * (float) $unitPrice, 2),
                    'stock_available' => $row['available'] >= $row['requested'],
                ]);
            }

            $request->update(['status' => SupplyRequest::STATUS_PROCESSING]);

            if ($partial) {
                SoimsNotification::create([
                    'user_id' => $request->requestor_id,
                    'type' => 'partial_availability',
                    'title' => 'Partial availability',
                    'message' => sprintf('Request %s can only be partially issued (RIS %s).', $request->request_no, $ris->ris_no),
                    'related_type' => Ris::class,
                    'related_id' => $ris->id,
                ]);
            }

            $ris->load('items');
            $this->audit->record($issuer, 'prepared', 'ris', $ris, null, [
                'ris_no' => $ris->ris_no,
                'supply_request_id' => $request->id,
                'status' => $ris->status,
                'items' => $ris->items->map(fn ($item) => ['item_id' => $item->item_id, 'issue_quantity' => $item->issue_quantity])->all(),
            ], null, $ipAddress, $userAgent);

            return $ris;
        });
    }

    public function release(Ris $ris, User $issuer, array $input = [], ?string $ipAddress = null, ?string $userAgent = null): Ris
    {
        return DB::transaction(function () use ($ris, $issuer, $input, $ipAddress, $userAgent): Ris {
            $document = Ris::lockForUpdate()->findOrFail($ris->id);

            if ($document->status !== Ris::STATUS_PREPARED) {
                throw ValidationException::withMessages(['ris' => 'Only prepared RIS documents can be released.']);
            }

            $request = $document->supplyRequest()->lockForUpdate()->first();
            $lines = $document->items()->orderBy('id')->lockForUpdate()->get();
            $itemIds = $lines->pluck('item_id')->unique()->all();
            $inventories = Inventory::whereIn('item_id', $itemIds)->orderBy('item_id')->lockForUpdate()->get()->keyBy('item_id');
            $releasedAt = now();
            $releaseId = DB::table('releases')->insertGetId([
                'ris_id' => $document->id,
                'released_by' => $issuer->id,
                'received_by' => $document->received_by,
                'released_at' => $releasedAt,
                'status' => 'released',
                'notes' => $input['notes'] ?? null,
                'created_at' => $releasedAt,
                'updated_at' => $releasedAt,
            ]);

            foreach ($lines as $line) {
                $quantity = (float) $line->issue_quantity;
                if ($quantity <= 0) {
                    continue;
                }

                $inventory = $inventories->get($line->item_id);
                $available = $inventory
                    ? (float) $inventory->quantity_on_hand - (float) $inventory->quantity_reserved
                    : 0.0;
                if (! $inventory || $quantity - $available > 0.0005) {
                    throw ValidationException::withMessages([
                        'ris' => sprintf('Current available stock for %s is below the prepared issue quantity.', $line->description_snapshot),
                    ]);
                }

                $lots = InventoryLot::where('item_id', $line->item_id)
                    ->where('is_active', true)
                    ->where('quantity_on_hand', '>', 0)
                    ->orderByRaw('expiration_date IS NULL')
                    ->orderBy('expiration_date')
                    ->orderBy('received_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $trackedQuantity = (float) InventoryLot::where('item_id', $line->item_id)
                    ->where('is_active', true)
                    ->sum('quantity_on_hand');
                $untrackedQuantity = max(0, (float) $inventory->quantity_on_hand - $trackedQuantity);
                $remaining = $quantity;

                foreach ($lots as $lot) {
                    if ($lot->expiration_date && $lot->expiration_date->lt(today())) {
                        continue;
                    }

                    $taken = min($remaining, (float) $lot->quantity_on_hand);
                    if ($taken <= 0) {
                        continue;
                    }

                    $lot->decrement('quantity_on_hand', $taken);
                    $this->recordReleaseMovement($line, $lot, $taken, $issuer, $releasedAt, $releaseId, $lot->unit_cost ?? $line->unit_price);
                    $remaining = round($remaining - $taken, 3);
                    if ($remaining <= 0.0005) {
                        break;
                    }
                }

                if ($remaining > $untrackedQuantity + 0.0005) {
                    throw ValidationException::withMessages([
                        'ris' => sprintf('Non-expired lot and untracked stock for %s cannot cover the prepared issue quantity.', $line->description_snapshot),
                    ]);
                }

                if ($remaining > 0.0005) {
                    $this->recordReleaseMovement($line, null, $remaining, $issuer, $releasedAt, $releaseId, $line->unit_price);
                }

                $inventory->decrement('quantity_on_hand', $quantity);
                if ($line->supply_request_item_id) {
                    $requestLine = $request?->items()->lockForUpdate()->find($line->supply_request_item_id);
                    if ($requestLine) {
                        $requestLine->update([
                            'issued_quantity' => (float) $requestLine->issued_quantity + $quantity,
                            'unavailable_quantity' => (float) $requestLine->unavailable_quantity + (float) $line->not_available_quantity,
                        ]);
                    }
                }
            }

            $document->update(['status' => Ris::STATUS_RELEASED, 'issued_by' => $issuer->id, 'issued_at' => $releasedAt]);
            $request?->update(['status' => SupplyRequest::STATUS_COMPLETED, 'completed_at' => $releasedAt]);

            $updated = $document->fresh(['items', 'supplyRequest']);
            $this->audit->record($issuer, 'released', 'ris', $updated,
                ['status' => Ris::STATUS_PREPARED],
                ['status' => $updated->status, 'release_id' => $releaseId, 'notes' => $input['notes'] ?? null],
                $input['notes'] ?? null, $ipAddress, $userAgent);

            return $updated;
        });
    }

    private function recordReleaseMovement(RisItem $line, ?InventoryLot $lot, float $quantity, User $issuer, $releasedAt, int $releaseId, ?string $unitCost): void
    {
        InventoryTransaction::create([
            'item_id' => $line->item_id,
            'inventory_lot_id' => $lot?->id,
            'type' => 'release',
            'quantity' => -$quantity,
            'unit_cost' => $unitCost,
            'reference_type' => 'release',
            'reference_id' => $releaseId,
            'performed_by' => $issuer->id,
            'reason' => 'RIS '.$line->ris_id,
            'occurred_at' => $releasedAt,
        ]);
    }
}

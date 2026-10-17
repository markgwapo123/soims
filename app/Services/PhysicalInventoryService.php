<?php

namespace App\Services;

use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\Inventory;
use App\Models\PhysicalInventory;
use App\Models\PhysicalInventoryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhysicalInventoryService
{
    public function createDraft(array $attributes, User $actor, ?string $ipAddress, ?string $userAgent): PhysicalInventory
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress, $userAgent): PhysicalInventory {
            $inventoryIds = collect($attributes['inventory_ids'] ?? [])->unique()->sort()->values();
            $assetIds = collect($attributes['accountable_item_ids'] ?? [])->unique()->sort()->values();
            $inventories = Inventory::with('item')->whereIn('id', $inventoryIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $assets = AccountableItem::with('item')->whereIn('id', $assetIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($inventories->count() !== $inventoryIds->count() || $inventories->contains(fn ($row) => $row->item->classification !== 'consumable')) {
                throw ValidationException::withMessages(['inventory_ids' => 'Only consumable stock balances may be counted as inventory.']);
            }
            if ($assets->count() !== $assetIds->count() || $assets->contains(fn ($row) => $row->item->classification !== 'accountable')) {
                throw ValidationException::withMessages(['accountable_item_ids' => 'Only accountable assets may be counted as individual units.']);
            }

            $document = PhysicalInventory::create([
                'physical_inventory_uuid' => (string) Str::uuid(),
                'inventory_no' => $attributes['inventory_no'],
                'conducted_by' => $actor->id,
                'department_id' => $attributes['department_id'] ?? null,
                'office_id' => $attributes['office_id'] ?? null,
                'conducted_at' => $attributes['conducted_at'],
                'status' => PhysicalInventory::STATUS_DRAFT,
                'remarks' => $attributes['remarks'] ?? null,
            ]);

            foreach ($inventories as $inventory) {
                PhysicalInventoryItem::create([
                    'physical_inventory_id' => $document->id,
                    'item_id' => $inventory->item_id,
                    'article' => $inventory->item->name,
                    'description' => $inventory->item->description ?: $inventory->item->name,
                    'unit_of_measure' => $inventory->item->unit_of_measure,
                    'unit_value' => $inventory->item->default_unit_price,
                    'quantity_per_property_card' => $inventory->quantity_on_hand,
                    'quantity_per_physical_count' => 0,
                ]);
            }

            foreach ($assets as $asset) {
                PhysicalInventoryItem::create([
                    'physical_inventory_id' => $document->id,
                    'item_id' => $asset->item_id,
                    'accountable_item_id' => $asset->id,
                    'article' => $asset->item->name,
                    'description' => $asset->item->description ?: $asset->item->name,
                    'date_acquired' => $asset->date_acquired,
                    'old_property_no' => $asset->property_no,
                    'unit_of_measure' => $asset->item->unit_of_measure,
                    'unit_value' => $asset->unit_value,
                    'quantity_per_property_card' => 1,
                    'quantity_per_physical_count' => 0,
                ]);
            }

            $this->audit($actor, 'created', $document, null, [
                'inventory_no' => $document->inventory_no,
                'status' => $document->status,
                'line_count' => $inventories->count() + $assets->count(),
            ], $attributes['remarks'] ?? null, $ipAddress, $userAgent);

            return $document->load('items');
        });
    }

    public function complete(PhysicalInventory $physicalInventory, array $attributes, User $actor, ?string $ipAddress, ?string $userAgent): PhysicalInventory
    {
        return DB::transaction(function () use ($physicalInventory, $attributes, $actor, $ipAddress, $userAgent): PhysicalInventory {
            $document = PhysicalInventory::lockForUpdate()->findOrFail($physicalInventory->id);
            if ($document->status !== PhysicalInventory::STATUS_DRAFT) {
                throw ValidationException::withMessages(['inventory' => 'Only draft physical inventories can be completed.']);
            }

            $lines = PhysicalInventoryItem::where('physical_inventory_id', $document->id)->orderBy('id')->lockForUpdate()->get();
            $counts = collect($attributes['counts'] ?? []);
            $lineIds = $lines->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $countIds = $counts->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($lines->isEmpty() || $lineIds !== $countIds) {
                throw ValidationException::withMessages(['counts' => 'Submit a physical quantity for every recorded line.']);
            }

            foreach ($lines as $line) {
                $count = $counts->get((string) $line->id, $counts->get($line->id));
                $physicalQuantity = (float) $count['quantity_per_physical_count'];
                $cardQuantity = (float) $line->quantity_per_property_card;
                $line->update([
                    'quantity_per_physical_count' => $physicalQuantity,
                    'shortage_quantity' => max($cardQuantity - $physicalQuantity, 0),
                    'overage_quantity' => max($physicalQuantity - $cardQuantity, 0),
                    'new_property_no' => $count['new_property_no'] ?? null,
                    'remarks' => $count['remarks'] ?? $line->remarks,
                ]);

                if ($line->item_id && ! $line->accountable_item_id) {
                    Inventory::where('item_id', $line->item_id)->update(['last_counted_at' => $document->conducted_at]);
                }
            }

            $foundItems = collect($attributes['found_items'] ?? [])->filter(fn ($row) => filled($row['article'] ?? null));
            foreach ($foundItems as $row) {
                $physicalQuantity = (float) $row['quantity_per_physical_count'];
                PhysicalInventoryItem::create([
                    'physical_inventory_id' => $document->id,
                    'article' => $row['article'],
                    'description' => $row['description'],
                    'unit_of_measure' => $row['unit_of_measure'] ?? null,
                    'unit_value' => $row['unit_value'] ?? null,
                    'quantity_per_property_card' => 0,
                    'quantity_per_physical_count' => $physicalQuantity,
                    'overage_quantity' => $physicalQuantity,
                    'new_property_no' => $row['new_property_no'] ?? null,
                    'remarks' => $row['remarks'] ?? null,
                ]);
            }

            $previous = $document->status;
            $document->update(['status' => PhysicalInventory::STATUS_COMPLETED]);
            $this->audit($actor, 'completed', $document, ['status' => $previous], [
                'status' => PhysicalInventory::STATUS_COMPLETED,
                'lines' => $document->items()->get(['article', 'quantity_per_property_card', 'quantity_per_physical_count', 'shortage_quantity', 'overage_quantity'])->toArray(),
            ], $document->remarks, $ipAddress, $userAgent);

            return $document->fresh(['items.item', 'items.accountableItem', 'conductor', 'department', 'office']);
        });
    }

    public function cancel(PhysicalInventory $physicalInventory, User $actor, ?string $ipAddress, ?string $userAgent): PhysicalInventory
    {
        return DB::transaction(function () use ($physicalInventory, $actor, $ipAddress, $userAgent): PhysicalInventory {
            $document = PhysicalInventory::lockForUpdate()->findOrFail($physicalInventory->id);
            if ($document->status !== PhysicalInventory::STATUS_DRAFT) {
                throw ValidationException::withMessages(['inventory' => 'Only draft physical inventories can be cancelled.']);
            }

            $document->update(['status' => PhysicalInventory::STATUS_CANCELLED]);
            $this->audit($actor, 'cancelled', $document, ['status' => PhysicalInventory::STATUS_DRAFT], ['status' => PhysicalInventory::STATUS_CANCELLED], $document->remarks, $ipAddress, $userAgent);

            return $document->fresh();
        });
    }

    private function audit(User $actor, string $action, PhysicalInventory $record, ?array $previous, array $new, ?string $reason, ?string $ipAddress, ?string $userAgent): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'role_snapshot' => $actor->roles()->wherePivotNull('revoked_at')->pluck('roles.key')->join(','),
            'action' => $action,
            'module' => 'physical_inventory',
            'record_type' => $record::class,
            'record_id' => $record->id,
            'previous_value' => $previous,
            'new_value' => $new,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => now(),
        ]);
    }
}
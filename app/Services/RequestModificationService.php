<?php

namespace App\Services;

use App\Models\Item;
use App\Models\RequestModification;
use App\Models\SoimsNotification;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestModificationService
{
    public function __construct(private readonly ApprovalService $approvals, private readonly AuditLogService $audit, private readonly StockReservationService $reservations) {}

    /** Confirmed requests the user may modify: Supply Office sees all, approvers only their type. */
    public function modifiable(User $user): Builder
    {
        $query = SupplyRequest::query()->where('status', SupplyRequest::STATUS_CONFIRMED);

        if ($user->hasPermission('issue_supplies')) {
            return $query;
        }

        $types = $user->hasPermission('approve_supply_requests') ? $this->approvals->reviewableTypes($user) : [];

        return $query->whereIn('request_type', $types);
    }

    public function modify(SupplyRequest $supplyRequest, User $user, array $lines, string $reason, ?string $ipAddress = null, ?string $userAgent = null): SupplyRequest
    {
        return DB::transaction(function () use ($supplyRequest, $user, $lines, $reason, $ipAddress, $userAgent): SupplyRequest {
            $request = SupplyRequest::lockForUpdate()->findOrFail($supplyRequest->id);

            if ($request->status !== SupplyRequest::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['reason' => 'Only confirmed requests can be modified.']);
            }

            $role = $user->roles()->wherePivotNull('revoked_at')->pluck('roles.key')->join(',');
            $rows = $request->items()->with(['item', 'confirmedItem'])->get()->keyBy('id');
            $changes = 0;
            $previousChanges = [];
            $newChanges = [];

            foreach ($lines as $line) {
                $row = $rows[$line['id']] ?? throw ValidationException::withMessages(['lines' => 'Invalid request line.']);

                if (($line['confirmed_quantity'] ?? '') !== '' && $line['confirmed_quantity'] !== null) {
                    $new = number_format((float) $line['confirmed_quantity'], 3, '.', '');

                    if ($new !== $row->confirmed_quantity) {
                        $this->record($request, $row->id, 'confirmed_quantity', ['quantity' => $row->confirmed_quantity], ['quantity' => $new], $reason, $user, $role);
                        $previousChanges[] = ['line_id' => $row->id, 'field' => 'confirmed_quantity', 'value' => $row->confirmed_quantity];
                        $newChanges[] = ['line_id' => $row->id, 'field' => 'confirmed_quantity', 'value' => $new];
                        $row->confirmed_quantity = $new;
                        $changes++;
                    }
                }

                $currentItemId = $row->confirmed_item_id ?? $row->item_id;
                $newItemId = (int) ($line['confirmed_item_id'] ?? 0);

                if ($newItemId > 0 && $newItemId !== $currentItemId) {
                    $newItem = Item::findOrFail($newItemId);

                    if (! $newItem->is_active) {
                        throw ValidationException::withMessages(['lines' => 'Inactive items cannot be confirmed.']);
                    }

                    $previous = $row->confirmedItem ?? $row->item;
                    $this->record($request, $row->id, 'confirmed_item_id',
                        ['item_id' => $previous->id, 'stock_no' => $previous->stock_no, 'name' => $previous->name],
                        ['item_id' => $newItem->id, 'stock_no' => $newItem->stock_no, 'name' => $newItem->name],
                        $reason, $user, $role);
                    $previousChanges[] = ['line_id' => $row->id, 'field' => 'confirmed_item_id', 'value' => $previous->id];
                    $newChanges[] = ['line_id' => $row->id, 'field' => 'confirmed_item_id', 'value' => $newItem->id];
                    $row->confirmed_item_id = $newItem->id;
                    $changes++;
                }

                $row->save();
            }

            if ($changes === 0) {
                throw ValidationException::withMessages(['lines' => 'No changes were made.']);
            }

            $this->reservations->synchronize($request);

            SoimsNotification::create([
                'user_id' => $request->requestor_id,
                'type' => 'request_modified',
                'title' => 'Request modified',
                'message' => sprintf('Confirmed items or quantities of request %s were modified.', $request->request_no),
                'related_type' => SupplyRequest::class,
                'related_id' => $request->id,
            ]);

            $updated = $request->fresh();
            $this->audit->record($user, 'modified', 'requests', $updated,
                ['changes' => $previousChanges], ['changes' => $newChanges], $reason, $ipAddress, $userAgent);

            return $updated;
        });
    }

    private function record(SupplyRequest $request, int $lineId, string $field, array $previous, array $new, string $reason, User $user, string $role): void
    {
        RequestModification::create([
            'supply_request_id' => $request->id,
            'supply_request_item_id' => $lineId,
            'field_name' => $field,
            'previous_value' => $previous,
            'new_value' => $new,
            'reason' => $reason,
            'modified_by' => $user->id,
            'role_snapshot' => $role,
            'modified_at' => now(),
        ]);
    }
}

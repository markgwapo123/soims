<?php

namespace App\Services;

use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\ItemAssignment;
use App\Models\ItemTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountableItemService
{
    public function register(array $attributes, User $actor, ?string $ipAddress, ?string $userAgent): AccountableItem
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress, $userAgent): AccountableItem {
            $asset = AccountableItem::create($attributes);

            AuditLog::create([
                'user_id' => $actor->id,
                'role_snapshot' => $actor->roles()->wherePivotNull('revoked_at')->pluck('roles.key')->join(','),
                'action' => 'created',
                'module' => 'accountability',
                'record_type' => AccountableItem::class,
                'record_id' => $asset->id,
                'previous_value' => null,
                'new_value' => $asset->only(array_keys($attributes)),
                'reason' => null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'occurred_at' => now(),
            ]);

            return $asset;
        });
    }

    public function assign(AccountableItem $accountableItem, array $attributes, User $actor, ?string $ipAddress, ?string $userAgent): ItemAssignment
    {
        return DB::transaction(function () use ($accountableItem, $attributes, $actor, $ipAddress, $userAgent): ItemAssignment {
            $asset = AccountableItem::lockForUpdate()->findOrFail($accountableItem->id);
            $activeAssignments = ItemAssignment::where('accountable_item_id', $asset->id)
                ->whereNull('released_at')->lockForUpdate()->get();

            if ($activeAssignments->isNotEmpty()) {
                throw ValidationException::withMessages(['assignment' => 'This asset already has an active assignment. Use transfer to change its custodian.']);
            }

            $previous = $this->currentValues($asset);
            $assignment = $asset->assignments()->create($this->assignmentValues($attributes, $actor));
            $this->updateCurrentValues($asset, $attributes);
            $this->recordAudit($asset, $actor, 'assigned', $previous, $this->currentValues($asset->fresh()), $attributes['reason'] ?? null, $ipAddress, $userAgent);

            return $assignment->fresh(['assignedUser', 'assignedDepartment', 'assignedOffice', 'assignedBy']);
        });
    }

    public function transfer(AccountableItem $accountableItem, array $attributes, User $actor, ?string $ipAddress, ?string $userAgent): ItemTransfer
    {
        return DB::transaction(function () use ($accountableItem, $attributes, $actor, $ipAddress, $userAgent): ItemTransfer {
            $asset = AccountableItem::lockForUpdate()->findOrFail($accountableItem->id);
            $activeAssignments = ItemAssignment::where('accountable_item_id', $asset->id)
                ->whereNull('released_at')->orderBy('id')->lockForUpdate()->get();

            if ($activeAssignments->count() !== 1) {
                throw ValidationException::withMessages(['assignment' => 'Transfer requires exactly one active assignment.']);
            }

            $previousAssignment = $activeAssignments->first();
            $previous = $this->currentValues($asset);
            $transferredAt = now();
            $previousAssignment->update([
                'released_at' => $transferredAt,
                'release_reason' => $attributes['reason'] ?? null,
                'status' => ItemAssignment::STATUS_TRANSFERRED,
            ]);

            $nextAssignment = $asset->assignments()->create($this->assignmentValues($attributes, $actor, $transferredAt));
            $this->updateCurrentValues($asset, $attributes);

            $transfer = ItemTransfer::create([
                'accountable_item_id' => $asset->id,
                'from_assignment_id' => $previousAssignment->id,
                'to_assignment_id' => $nextAssignment->id,
                'from_location' => $previousAssignment->location,
                'to_location' => $nextAssignment->location,
                'transferred_by' => $actor->id,
                'reason' => $attributes['reason'] ?? null,
                'transferred_at' => $transferredAt,
            ]);

            $this->recordAudit($asset, $actor, 'transferred', $previous, $this->currentValues($asset->fresh()), $attributes['reason'] ?? null, $ipAddress, $userAgent);

            return $transfer->fresh(['fromAssignment', 'toAssignment', 'transferredBy']);
        });
    }

    private function assignmentValues(array $attributes, User $actor, $assignedAt = null): array
    {
        return [
            'assigned_to_user_id' => $attributes['assigned_to_user_id'] ?? null,
            'assigned_to_department_id' => $attributes['assigned_to_department_id'] ?? null,
            'assigned_to_office_id' => $attributes['assigned_to_office_id'] ?? null,
            'location' => $attributes['location'],
            'assigned_by' => $actor->id,
            'assigned_at' => $assignedAt ?? now(),
            'status' => ItemAssignment::STATUS_ACTIVE,
        ];
    }

    private function updateCurrentValues(AccountableItem $asset, array $attributes): void
    {
        $asset->update([
            'current_user_id' => $attributes['assigned_to_user_id'] ?? null,
            'current_department_id' => $attributes['assigned_to_department_id'] ?? null,
            'current_office_id' => $attributes['assigned_to_office_id'] ?? null,
            'current_location' => $attributes['location'],
        ]);
    }

    private function currentValues(AccountableItem $asset): array
    {
        return $asset->only(['current_user_id', 'current_department_id', 'current_office_id', 'current_location']);
    }

    private function recordAudit(AccountableItem $asset, User $actor, string $action, array $previous, array $new, ?string $reason, ?string $ipAddress, ?string $userAgent): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'role_snapshot' => $actor->roles()->wherePivotNull('revoked_at')->pluck('roles.key')->join(','),
            'action' => $action,
            'module' => 'accountability',
            'record_type' => AccountableItem::class,
            'record_id' => $asset->id,
            'previous_value' => $previous,
            'new_value' => $new,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => now(),
        ]);
    }
}
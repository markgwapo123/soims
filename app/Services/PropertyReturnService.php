<?php

namespace App\Services;

use App\Models\AccountableItem;
use App\Models\AuditLog;
use App\Models\ItemAssignment;
use App\Models\PropertyReturn;
use App\Models\PropertyReturnItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PropertyReturnService
{
    public function createDraft(array $attributes, User $actor, ?string $ipAddress, ?string $userAgent): PropertyReturn
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress, $userAgent): PropertyReturn {
            $assetIds = collect($attributes['items'])->unique()->sort()->values();
            $assets = AccountableItem::with('item')->whereIn('id', $assetIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($assets->count() !== $assetIds->count() || $assets->contains(fn ($asset) => $asset->item->classification !== 'accountable')) {
                throw ValidationException::withMessages(['items' => 'Only existing accountable assets may be added to a PRS.']);
            }

            $now = now();
            $propertyReturn = PropertyReturn::create([
                'property_return_uuid' => (string) Str::uuid(),
                'prs_no' => $attributes['prs_no'],
                'document_no' => $attributes['document_no'] ?? null,
                'issue_no' => $attributes['issue_no'] ?? null,
                'revision_no' => 0,
                'office_name' => $attributes['office_name'],
                'purpose' => $attributes['purpose'],
                'received_from' => $attributes['received_from'] ?? null,
                'returned_to' => $attributes['returned_to'] ?? null,
                'end_user' => $attributes['end_user'] ?? null,
                'certification' => $attributes['certification'] ?? null,
                'returned_by' => $actor->id,
                'received_at' => $now,
                'status' => PropertyReturn::STATUS_DRAFT,
            ]);

            foreach ($attributes['items'] as $assetId) {
                $asset = $assets->get((int) $assetId);
                PropertyReturnItem::create([
                    'property_return_id' => $propertyReturn->id,
                    'accountable_item_id' => $asset->id,
                    'property_no_snapshot' => $asset->property_no,
                    'description_snapshot' => trim($asset->item->name.' '.$asset->item->description),
                    'quantity' => 1,
                    'unit_value' => $asset->unit_value,
                    'remarks' => $attributes['remarks'][$asset->id] ?? null,
                ]);
            }

            $this->recordAudit($actor, 'created', $propertyReturn, null, $propertyReturn->only(['prs_no', 'office_name', 'purpose', 'status']), null, $ipAddress, $userAgent);

            return $propertyReturn->load('items');
        });
    }

    public function complete(PropertyReturn $propertyReturn, User $actor, ?string $ipAddress, ?string $userAgent): PropertyReturn
    {
        return DB::transaction(function () use ($propertyReturn, $actor, $ipAddress, $userAgent): PropertyReturn {
            $document = PropertyReturn::lockForUpdate()->findOrFail($propertyReturn->id);
            if ($document->status !== PropertyReturn::STATUS_DRAFT) {
                throw ValidationException::withMessages(['prs' => 'Only draft PRS records can be completed.']);
            }

            $lines = PropertyReturnItem::where('property_return_id', $document->id)->orderBy('id')->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['prs' => 'A PRS must contain at least one return item.']);
            }

            $assets = AccountableItem::whereIn('id', $lines->pluck('accountable_item_id')->unique())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($assets->count() !== $lines->pluck('accountable_item_id')->unique()->count()) {
                throw ValidationException::withMessages(['prs' => 'One or more PRS assets are no longer available.']);
            }

            foreach ($lines as $line) {
                $asset = $assets->get($line->accountable_item_id);
                $previous = $asset->only(['status', 'current_user_id', 'current_department_id', 'current_office_id', 'current_location']);
                $activeAssignments = ItemAssignment::where('accountable_item_id', $asset->id)
                    ->whereNull('released_at')->orderBy('id')->lockForUpdate()->get();

                if ($activeAssignments->count() > 1) {
                    throw ValidationException::withMessages(['prs' => 'An asset has multiple active assignments; resolve its assignment history before completing this PRS.']);
                }

                if ($activeAssignments->isNotEmpty()) {
                    $activeAssignments->first()->update([
                        'released_at' => now(),
                        'release_reason' => 'Returned under PRS '.$document->prs_no,
                        'status' => ItemAssignment::STATUS_RETURNED,
                    ]);
                }

                $assetStatus = match ($document->purpose) {
                    PropertyReturn::PURPOSE_REPAIR => 'FOR_REPAIR',
                    PropertyReturn::PURPOSE_DISPOSAL => 'FOR_DISPOSAL',
                    PropertyReturn::PURPOSE_RETURNED_TO_STOCK => 'IN_STOCK',
                    PropertyReturn::PURPOSE_OTHER => 'RETURNED',
                };
                $asset->update([
                    'status' => $assetStatus,
                    'current_user_id' => null,
                    'current_department_id' => null,
                    'current_office_id' => null,
                    'current_location' => $document->returned_to ?: $document->office_name,
                ]);

                $this->recordAudit(
                    $actor,
                    'returned',
                    $asset,
                    $previous,
                    $asset->fresh()->only(['status', 'current_user_id', 'current_department_id', 'current_office_id', 'current_location']),
                    $document->prs_no.' / '.$document->purpose,
                    $ipAddress,
                    $userAgent,
                );
            }

            $previousStatus = $document->status;
            $document->update(['status' => PropertyReturn::STATUS_COMPLETED]);
            $this->recordAudit(
                $actor,
                'completed',
                $document,
                ['status' => $previousStatus],
                ['status' => PropertyReturn::STATUS_COMPLETED, 'purpose' => $document->purpose],
                $document->certification,
                $ipAddress,
                $userAgent,
            );

            return $document->fresh(['items.accountableItem', 'returner']);
        });
    }

    public function cancel(PropertyReturn $propertyReturn, User $actor, ?string $ipAddress, ?string $userAgent): PropertyReturn
    {
        return DB::transaction(function () use ($propertyReturn, $actor, $ipAddress, $userAgent): PropertyReturn {
            $document = PropertyReturn::lockForUpdate()->findOrFail($propertyReturn->id);
            if ($document->status !== PropertyReturn::STATUS_DRAFT) {
                throw ValidationException::withMessages(['prs' => 'Only draft PRS records can be cancelled.']);
            }

            $document->update(['status' => PropertyReturn::STATUS_CANCELLED]);
            $this->recordAudit(
                $actor,
                'cancelled',
                $document,
                ['status' => PropertyReturn::STATUS_DRAFT],
                ['status' => PropertyReturn::STATUS_CANCELLED],
                $document->certification,
                $ipAddress,
                $userAgent,
            );

            return $document->fresh();
        });
    }

    private function recordAudit(User $actor, string $action, $record, ?array $previous, array $new, ?string $reason, ?string $ipAddress, ?string $userAgent): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'role_snapshot' => $actor->roles()->wherePivotNull('revoked_at')->pluck('roles.key')->join(','),
            'action' => $action,
            'module' => 'property_returns',
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
<?php

namespace App\Services;

use App\Models\Item;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupplyRequestService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function submit(User $requestor, array $data, ?string $ipAddress = null, ?string $userAgent = null): SupplyRequest
    {
        $department = $requestor->department ?? $requestor->office?->department;

        if (! $department || ! $department->is_active) {
            throw ValidationException::withMessages([
                'purpose' => 'Your account must be assigned to an active department or office before submitting requests.',
            ]);
        }

        // Academic departments route to DAA; non-academic and administrative to the Administrative Officer.
        $type = $department->type === 'academic'
            ? SupplyRequest::TYPE_ACADEMIC
            : SupplyRequest::TYPE_NON_ACADEMIC;

        return DB::transaction(function () use ($requestor, $department, $type, $data, $ipAddress, $userAgent): SupplyRequest {
            $request = SupplyRequest::create([
                'request_uuid' => (string) Str::uuid(),
                'request_no' => (string) Str::uuid(),
                'requestor_id' => $requestor->id,
                'department_id' => $department->id,
                'office_id' => $requestor->office_id,
                'request_type' => $type,
                'purpose' => $data['purpose'],
                'status' => SupplyRequest::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $request->update(['request_no' => sprintf('REQ-%s-%06d', now()->format('Y'), $request->id)]);

            $prices = Item::whereIn('id', array_column($data['items'], 'item_id'))->pluck('default_unit_price', 'id');

            foreach ($data['items'] as $row) {
                $request->items()->create([
                    'item_id' => $row['item_id'],
                    'requested_quantity' => $row['quantity'],
                    'unit_price_snapshot' => $prices[$row['item_id']] ?? null,
                ]);
            }

            $request->load('items.item');
            $this->audit->record($requestor, 'submitted', 'requests', $request, null, [
                'request_no' => $request->request_no,
                'status' => $request->status,
                'request_type' => $request->request_type,
                'purpose' => $request->purpose,
                'items' => $request->items->map(fn ($item) => ['item_id' => $item->item_id, 'quantity' => $item->requested_quantity])->all(),
            ], null, $ipAddress, $userAgent);

            return $request;
        });
    }
}

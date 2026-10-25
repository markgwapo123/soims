<?php

namespace App\Services;

use App\Models\RequestApproval;
use App\Models\SoimsNotification;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(private readonly AuditLogService $audit, private readonly StockReservationService $reservations) {}

    /** @return list<string> */
    public function reviewableTypes(User $user): array
    {
        if ($user->hasRole('supply_administrator')) {
            return [SupplyRequest::TYPE_ACADEMIC, SupplyRequest::TYPE_NON_ACADEMIC];
        }

        $types = [];
        if ($user->hasRole('daa')) {
            $types[] = SupplyRequest::TYPE_ACADEMIC;
        }
        if ($user->hasRole('administrative_officer')) {
            $types[] = SupplyRequest::TYPE_NON_ACADEMIC;
        }

        return $types;
    }

    public function decide(SupplyRequest $supplyRequest, User $approver, string $decision, ?string $remarks, ?string $ipAddress = null, ?string $userAgent = null): SupplyRequest
    {
        return DB::transaction(function () use ($supplyRequest, $approver, $decision, $remarks, $ipAddress, $userAgent): SupplyRequest {
            $request = SupplyRequest::lockForUpdate()->findOrFail($supplyRequest->id);

            if ($request->status !== SupplyRequest::STATUS_SUBMITTED) {
                throw ValidationException::withMessages(['decision' => 'This request has already been reviewed.']);
            }

            $approved = $decision === 'approve';

            RequestApproval::create([
                'supply_request_id' => $request->id,
                'approver_id' => $approver->id,
                'role_snapshot' => $approver->roles()->wherePivotNull('revoked_at')->pluck('roles.key')->join(','),
                'decision' => $approved ? 'approved' : 'rejected',
                'comments' => $remarks,
                'decided_at' => now(),
            ]);

            if ($approved) {
                $request->items()->update(['confirmed_quantity' => DB::raw('requested_quantity')]);
                $request->update(['status' => SupplyRequest::STATUS_CONFIRMED, 'confirmed_at' => now()]);
                $this->reservations->synchronize($request);
            } else {
                $request->update([
                    'status' => SupplyRequest::STATUS_DENIED,
                    'rejected_at' => now(),
                    'rejection_reason' => $remarks,
                ]);
            }

            SoimsNotification::create([
                'user_id' => $request->requestor_id,
                'type' => $approved ? 'request_approved' : 'request_denied',
                'title' => $approved ? 'Request approved' : 'Request denied',
                'message' => sprintf('Request %s was %s.', $request->request_no, $approved ? 'approved' : 'denied'),
                'related_type' => SupplyRequest::class,
                'related_id' => $request->id,
            ]);

            $updated = $request->fresh();
            $this->audit->record($approver, $approved ? 'approved' : 'rejected', 'requests', $updated,
                ['status' => SupplyRequest::STATUS_SUBMITTED],
                ['status' => $updated->status, 'decision' => $approved ? 'approved' : 'rejected'],
                $remarks, $ipAddress, $userAgent);

            return $updated;
        });
    }
}

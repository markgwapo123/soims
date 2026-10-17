<?php

namespace App\Policies;

use App\Models\SupplyRequest;
use App\Models\User;
use App\Services\ApprovalService;

class SupplyRequestPolicy
{
    public function view(User $user, SupplyRequest $supplyRequest): bool
    {
        return $supplyRequest->requestor_id === $user->id;
    }

    public function review(User $user, SupplyRequest $supplyRequest): bool
    {
        return $user->hasPermission('approve_supply_requests')
            && in_array($supplyRequest->request_type, app(ApprovalService::class)->reviewableTypes($user), true);
    }

    public function modify(User $user, SupplyRequest $supplyRequest): bool
    {
        if ($supplyRequest->status !== SupplyRequest::STATUS_CONFIRMED) {
            return false;
        }

        return $user->hasPermission('issue_supplies') || $this->review($user, $supplyRequest);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideSupplyRequestRequest;
use App\Models\SupplyRequest;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvals) {}

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['confirmed', 'denied'], true)
            ? $request->query('status')
            : SupplyRequest::STATUS_SUBMITTED;

        return view('approvals.index', [
            'status' => $status,
            'requests' => SupplyRequest::with(['requestor', 'department'])
                ->withCount('items')
                ->whereIn('request_type', $this->approvals->reviewableTypes($request->user()))
                ->where('status', $status)
                ->latest('submitted_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(SupplyRequest $supplyRequest): View
    {
        Gate::authorize('review', $supplyRequest);

        return view('approvals.show', [
            'supplyRequest' => $supplyRequest->load(['items.item', 'department', 'requestor', 'approvals.approver']),
        ]);
    }

    public function decide(DecideSupplyRequestRequest $request, SupplyRequest $supplyRequest): RedirectResponse
    {
        Gate::authorize('review', $supplyRequest);

        $this->approvals->decide($supplyRequest, $request->user(), $request->validated('decision'), $request->validated('remarks'), $request->ip(), $request->userAgent());

        return redirect()->route('approvals.index')->with('status', 'Decision recorded.');
    }
}

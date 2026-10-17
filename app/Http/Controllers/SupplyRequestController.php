<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplyRequestRequest;
use App\Models\Item;
use App\Models\SupplyRequest;
use App\Services\SupplyRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplyRequestController extends Controller
{
    public function __construct(private readonly SupplyRequestService $requests) {}

    public function index(Request $request): View
    {
        return view('requests.index', [
            'requests' => SupplyRequest::where('requestor_id', $request->user()->id)
                ->withCount('items')
                ->latest('submitted_at')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('requests.create', [
            'items' => Item::with('inventory')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSupplyRequestRequest $request): RedirectResponse
    {
        $supplyRequest = $this->requests->submit($request->user(), $request->validated(), $request->ip(), $request->userAgent());

        return redirect()->route('requests.show', $supplyRequest)->with('status', 'Request submitted.');
    }

    public function show(SupplyRequest $supplyRequest): View
    {
        Gate::authorize('view', $supplyRequest);

        return view('requests.show', ['supplyRequest' => $supplyRequest->load(['items.item', 'items.confirmedItem', 'department', 'approvals.approver', 'modifications.modifier'])]);
    }
}

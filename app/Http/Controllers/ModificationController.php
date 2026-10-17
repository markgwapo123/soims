<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModifyConfirmedRequestRequest;
use App\Models\Item;
use App\Models\SupplyRequest;
use App\Services\RequestModificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ModificationController extends Controller
{
    public function __construct(private readonly RequestModificationService $modifications) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('issue_supplies') || $user->hasPermission('approve_supply_requests'), 403);

        return view('modifications.index', [
            'requests' => $this->modifications->modifiable($user)
                ->with(['requestor', 'department'])
                ->withCount('items')
                ->latest('confirmed_at')
                ->paginate(15),
        ]);
    }

    public function edit(SupplyRequest $supplyRequest): View
    {
        Gate::authorize('modify', $supplyRequest);

        $supplyRequest->load(['items.item', 'items.confirmedItem', 'modifications.modifier']);
        $usedItemIds = $supplyRequest->items->map(fn ($line) => $line->confirmed_item_id ?? $line->item_id);

        return view('modifications.edit', [
            'supplyRequest' => $supplyRequest,
            'items' => Item::where('is_active', true)->orWhereIn('id', $usedItemIds)->orderBy('name')->get(),
        ]);
    }

    public function update(ModifyConfirmedRequestRequest $request, SupplyRequest $supplyRequest): RedirectResponse
    {
        $this->modifications->modify($supplyRequest, $request->user(), $request->validated('lines'), $request->validated('reason'), $request->ip(), $request->userAgent());

        return redirect()->route('modifications.edit', $supplyRequest)->with('status', 'Modification recorded.');
    }
}

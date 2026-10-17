<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyReturnRequest;
use App\Models\AccountableItem;
use App\Models\PropertyReturn;
use App\Services\PropertyReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyReturnController extends Controller
{
    public function __construct(private readonly PropertyReturnService $propertyReturns) {}

    public function index(): View
    {
        return view('property-returns.index', [
            'returns' => PropertyReturn::with('returner')->withCount('items')->latest('received_at')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('property-returns.create', [
            'assets' => AccountableItem::with(['item', 'currentUser', 'currentDepartment', 'currentOffice'])
                ->whereHas('item', fn ($query) => $query->where('classification', 'accountable'))
                ->orderBy('property_no')->get(),
        ]);
    }

    public function store(StorePropertyReturnRequest $request): RedirectResponse
    {
        $propertyReturn = $this->propertyReturns->createDraft(
            $request->validated(), $request->user(), $request->ip(), $request->userAgent(),
        );

        return redirect()->route('property-returns.show', $propertyReturn)->with('status', 'PRS draft created.');
    }

    public function show(PropertyReturn $propertyReturn): View
    {
        return view('property-returns.show', [
            'propertyReturn' => $propertyReturn->load(['items.accountableItem.item', 'returner']),
        ]);
    }

    public function complete(Request $request, PropertyReturn $propertyReturn): RedirectResponse
    {
        $this->propertyReturns->complete($propertyReturn, $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('property-returns.show', $propertyReturn)->with('status', 'PRS completed; asset status and assignment history updated.');
    }

    public function cancel(Request $request, PropertyReturn $propertyReturn): RedirectResponse
    {
        $this->propertyReturns->cancel($propertyReturn, $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('property-returns.show', $propertyReturn)->with('status', 'PRS cancelled.');
    }
}
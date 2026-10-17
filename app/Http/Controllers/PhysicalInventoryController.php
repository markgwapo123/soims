<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompletePhysicalInventoryRequest;
use App\Http\Requests\StorePhysicalInventoryRequest;
use App\Models\AccountableItem;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Office;
use App\Models\PhysicalInventory;
use App\Services\PhysicalInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhysicalInventoryController extends Controller
{
    public function __construct(private readonly PhysicalInventoryService $physicalInventories) {}

    public function index(): View
    {
        return view('physical-inventories.index', [
            'inventories' => PhysicalInventory::with(['conductor', 'department', 'office'])->withCount('items')->latest('conducted_at')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('physical-inventories.create', [
            'inventories' => Inventory::with('item')->whereHas('item', fn ($query) => $query->where('classification', 'consumable'))->orderBy('item_id')->get(),
            'assets' => AccountableItem::with('item')->orderBy('property_no')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StorePhysicalInventoryRequest $request): RedirectResponse
    {
        $inventory = $this->physicalInventories->createDraft($request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('physical-inventories.show', $inventory)->with('status', 'Physical inventory draft created. Enter the physical count for each line to complete it.');
    }

    public function show(PhysicalInventory $physicalInventory): View
    {
        return view('physical-inventories.show', [
            'physicalInventory' => $physicalInventory->load(['items.item', 'items.accountableItem', 'conductor', 'department', 'office']),
        ]);
    }

    public function complete(CompletePhysicalInventoryRequest $request, PhysicalInventory $physicalInventory): RedirectResponse
    {
        $this->physicalInventories->complete($physicalInventory, $request->validated(), $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('physical-inventories.show', $physicalInventory)->with('status', 'Physical inventory completed. Discrepancies were recorded; stock balances were not adjusted.');
    }

    public function cancel(Request $request, PhysicalInventory $physicalInventory): RedirectResponse
    {
        $this->physicalInventories->cancel($physicalInventory, $request->user(), $request->ip(), $request->userAgent());

        return redirect()->route('physical-inventories.show', $physicalInventory)->with('status', 'Physical inventory draft cancelled.');
    }
}
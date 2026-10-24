<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiveStockRequest;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Services\InventoryService;
use App\Services\ExpirationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory, private readonly ExpirationService $expiration) {}

    public function index(): View
    {
        return view('admin.inventory.index', [
            'inventories' => Inventory::with('item')->orderByDesc('updated_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.inventory.receive', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ReceiveStockRequest $request): RedirectResponse
    {
        $this->inventory->receive($request->validated(), $request->user(), $request->ip(), $request->userAgent());
        return redirect()->route('admin.inventory.index')->with('status', 'Stock received and ledger updated.');
    }

    public function stockCard(Item $item): View
    {
        return view('admin.inventory.stock-card', ['item' => $item, 'transactions' => $this->inventory->stockCard($item->id)]);
    }

    public function expirationReport(?string $status = null): View
    {
        abort_unless($status === null || in_array($status, ['valid', 'near_expiration', 'expired'], true), 404);

        $lots = $this->expiration->lots($status);

        return view('admin.inventory.expiration', [
            'lots' => $lots,
            'statuses' => $lots->mapWithKeys(fn ($lot) => [$lot->id => $this->expiration->status($lot)]),
            'status' => $status,
            'warningDays' => config('soims.expiration_warning_days'),
        ]);
    }
}

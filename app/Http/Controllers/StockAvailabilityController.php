<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\StockAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAvailabilityController extends Controller
{
    public function __invoke(Request $request, StockAvailabilityService $stock): View
    {
        $user = $request->user();
        abort_unless(
            $user->hasPermission('manage_inventory')
                || $user->hasPermission('submit_supply_requests')
                || $user->hasPermission('approve_supply_requests'),
            403
        );

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['nullable', 'in:consumable,accountable'],
            'availability' => ['nullable', 'in:available,low_stock,out_of_stock'],
        ]);
        $canManageInventory = $user->hasPermission('manage_inventory');

        return view('stock-availability.index', [
            'items' => $stock->paginate($filters, $canManageInventory),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
            'hasFilters' => collect($filters)->contains(fn ($value) => $value !== null && $value !== ''),
            'canManageInventory' => $canManageInventory,
            'summary' => $canManageInventory ? $stock->summary(true) : null,
            'statusLabels' => [
                'available' => 'Available',
                'low_stock' => 'Low Stock',
                'out_of_stock' => 'Out of Stock',
            ],
        ]);
    }
}
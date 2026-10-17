<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'stock_search' => ['nullable', 'string', 'max:120'],
            'stock_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'stock_type' => ['nullable', 'in:consumable,accountable'],
            'stock_availability' => ['nullable', 'in:available,low_stock,out_of_stock'],
        ]);

        return view('dashboard.index', $this->dashboard->dataFor($request->user(), [
            'search' => $filters['stock_search'] ?? '',
            'category_id' => $filters['stock_category_id'] ?? null,
            'type' => $filters['stock_type'] ?? null,
            'availability' => $filters['stock_availability'] ?? null,
        ]));
    }
}
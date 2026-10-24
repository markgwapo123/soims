<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockAvailabilityService
{
    private const AVAILABLE_CONSUMABLE_SQL = '(COALESCE(inventories.quantity_on_hand, 0) - COALESCE(inventories.quantity_reserved, 0))';
    private const AVAILABLE_ASSET_SQL = 'COALESCE(asset_counts.available_count, 0)';

    public function paginate(array $filters = [], bool $includeInactive = false, int $perPage = 25, string $pageName = 'page'): LengthAwarePaginator
    {
        $items = $this->itemsQuery($filters, $includeInactive)
            ->orderBy('items.name')
            ->orderBy('items.id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();

        foreach ($items as $item) {
            $current = $this->currentQuantity($item);
            $available = $this->availableQuantity($item);
            $item->setAttribute('stock_current_quantity', $item->classification === 'accountable'
                ? \App\Support\QuantityFormatter::display($current)
                : \App\Support\QuantityFormatter::display($current));
            $item->setAttribute('stock_available_quantity', $item->classification === 'accountable'
                ? \App\Support\QuantityFormatter::display($available)
                : \App\Support\QuantityFormatter::display($available));
            $item->setAttribute('stock_status', $this->status($item, $available));
        }

        return $items;
    }

    public function summary(bool $includeInactive = false): array
    {
        $summary = $this->summaryQuery($includeInactive)
            ->selectRaw('COUNT(items.id) AS total_items')
            ->selectRaw('COALESCE(SUM(COALESCE(inventories.quantity_on_hand, 0)), 0) AS current_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN items.classification = \'consumable\' AND '.self::AVAILABLE_CONSUMABLE_SQL.' > 0 THEN '.self::AVAILABLE_CONSUMABLE_SQL.' ELSE 0 END), 0) AS available_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN items.classification = \'accountable\' THEN '.self::AVAILABLE_ASSET_SQL.' ELSE 0 END), 0) AS available_assets')
            ->selectRaw('SUM(CASE WHEN '.self::AVAILABLE_FOR_STATUS_SQL.' THEN 1 ELSE 0 END) AS available_items')
            ->selectRaw('SUM(CASE WHEN '.self::LOW_FOR_STATUS_SQL.' THEN 1 ELSE 0 END) AS low_stock_items')
            ->selectRaw('SUM(CASE WHEN '.self::OUT_FOR_STATUS_SQL.' THEN 1 ELSE 0 END) AS out_of_stock_items')
            ->first();

        return [
            'total_items' => (int) $summary->total_items,
            'current_stock' => (float) $summary->current_stock,
            'available_stock' => (float) $summary->available_stock,
            'available_assets' => (int) $summary->available_assets,
            'available_items' => (int) $summary->available_items,
            'low_stock_items' => (int) $summary->low_stock_items,
            'out_of_stock_items' => (int) $summary->out_of_stock_items,
        ];
    }

    private const AVAILABLE_FOR_STATUS_SQL = "((items.classification = 'consumable' AND ".self::AVAILABLE_CONSUMABLE_SQL." > 0 AND (items.reorder_level IS NULL OR ".self::AVAILABLE_CONSUMABLE_SQL." > items.reorder_level)) OR (items.classification = 'accountable' AND ".self::AVAILABLE_ASSET_SQL." > 0 AND (items.reorder_level IS NULL OR ".self::AVAILABLE_ASSET_SQL." > items.reorder_level)))";
    private const LOW_FOR_STATUS_SQL = "((items.classification = 'consumable' AND ".self::AVAILABLE_CONSUMABLE_SQL." > 0 AND items.reorder_level IS NOT NULL AND ".self::AVAILABLE_CONSUMABLE_SQL." <= items.reorder_level) OR (items.classification = 'accountable' AND ".self::AVAILABLE_ASSET_SQL." > 0 AND items.reorder_level IS NOT NULL AND ".self::AVAILABLE_ASSET_SQL." <= items.reorder_level))";
    private const OUT_FOR_STATUS_SQL = '((items.classification = \'consumable\' AND '.self::AVAILABLE_CONSUMABLE_SQL.' <= 0) OR (items.classification = \'accountable\' AND '.self::AVAILABLE_ASSET_SQL.' <= 0))';

    private function itemsQuery(array $filters, bool $includeInactive): Builder
    {
        $query = $this->joinedItemQuery($includeInactive)
            ->with(['category', 'inventory'])
            ->addSelect(DB::raw('COALESCE(asset_counts.registered_count, 0) AS registered_assets_count'))
            ->addSelect(DB::raw(self::AVAILABLE_ASSET_SQL.' AS available_assets_count'));

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $matches) use ($search): void {
                $matches->where('items.name', 'like', '%'.$search.'%')
                    ->orWhere('items.stock_no', 'like', '%'.$search.'%')
                    ->orWhereHas('category', fn (Builder $category) => $category
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%'));
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('items.category_id', $filters['category_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('items.classification', $filters['type']);
        }
        if (! empty($filters['availability'])) {
            $availabilitySql = match ($filters['availability']) {
                'available' => self::AVAILABLE_FOR_STATUS_SQL,
                'low_stock' => self::LOW_FOR_STATUS_SQL,
                'out_of_stock' => self::OUT_FOR_STATUS_SQL,
            };
            $query->whereRaw($availabilitySql);
        }

        return $query;
    }

    private function joinedItemQuery(bool $includeInactive): Builder
    {
        $query = Item::query()
            ->leftJoin('inventories', 'inventories.item_id', '=', 'items.id')
            ->leftJoinSub($this->assetCountsQuery(), 'asset_counts', 'asset_counts.item_id', '=', 'items.id')
            ->select('items.*');

        if (! $includeInactive) {
            $query->where('items.is_active', true);
        }

        return $query;
    }

    private function summaryQuery(bool $includeInactive): QueryBuilder
    {
        $query = DB::table('items')
            ->leftJoin('inventories', 'inventories.item_id', '=', 'items.id')
            ->leftJoinSub($this->assetCountsQuery(), 'asset_counts', 'asset_counts.item_id', '=', 'items.id')
            ->whereNull('items.deleted_at');

        if (! $includeInactive) {
            $query->where('items.is_active', true);
        }

        return $query;
    }

    private function assetCountsQuery(): QueryBuilder
    {
        return DB::table('accountable_items')
            ->select('item_id')
            ->selectRaw('COUNT(*) AS registered_count')
            ->selectRaw("SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_count")
            ->whereNull('deleted_at')
            ->groupBy('item_id');
    }

    private function currentQuantity(Item $item): float
    {
        return $item->classification === 'accountable'
            ? (float) $item->registered_assets_count
            : (float) ($item->inventory?->quantity_on_hand ?? 0);
    }

    private function availableQuantity(Item $item): float
    {
        return $item->classification === 'accountable'
            ? (float) $item->available_assets_count
            : max(0, (float) ($item->inventory?->quantity_available ?? 0));
    }

    private function status(Item $item, float $available): string
    {
        if ($available <= 0) {
            return 'out_of_stock';
        }

        return $item->reorder_level !== null && $available <= (float) $item->reorder_level
            ? 'low_stock'
            : 'available';
    }
}
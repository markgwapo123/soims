<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\SupplyRequest;
use Illuminate\Support\Facades\DB;

class StockReservationService
{
    /**
     * Synchronize one confirmed request's reservation with available stock.
     * Caller must hold the supply request lock and be inside a transaction.
     * Reservations can be partial; stock is never deducted until RIS release.
     */
    public function synchronize(SupplyRequest $request): void
    {
        $lines = $request->items()->orderBy('id')->lockForUpdate()->get();
        $itemIds = $lines->flatMap(fn ($line) => [
            $line->item_id,
            $line->confirmed_item_id ?? $line->item_id,
        ])->unique()->sort()->values()->all();
        $inventories = Inventory::whereIn('item_id', $itemIds)
            ->orderBy('item_id')->lockForUpdate()->get()->keyBy('item_id');

        // Release this request's old reservations before recalculating.
        foreach ($lines as $line) {
            $old = (float) $line->reserved_quantity;
            if ($old > 0) {
                $itemId = $line->confirmed_item_id ?? $line->item_id;
                $inventory = $inventories->get($itemId);
                if ($inventory) {
                    $inventory->quantity_reserved = max(0, (float) $inventory->quantity_reserved - $old);
                    $inventory->save();
                }
            }
            $line->reserved_quantity = 0;
            $line->save();
        }

        foreach ($lines as $line) {
            $itemId = $line->confirmed_item_id ?? $line->item_id;
            $inventory = $inventories->get($itemId);
            if (! $inventory) {
                continue;
            }
            $free = max(0, (float) $inventory->quantity_on_hand - (float) $inventory->quantity_reserved);
            $reserved = min(max(0, (float) $line->confirmed_quantity), floor($free));
            if ($reserved <= 0) {
                continue;
            }
            $inventory->quantity_reserved = (float) $inventory->quantity_reserved + $reserved;
            $inventory->save();
            $line->reserved_quantity = $reserved;
            $line->save();
        }
    }
}

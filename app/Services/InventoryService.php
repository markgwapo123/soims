<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryLot;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function receive(array $attributes, User $performedBy, ?string $ipAddress = null, ?string $userAgent = null): Inventory
    {
        return DB::transaction(function () use ($attributes, $performedBy, $ipAddress, $userAgent): Inventory {
            // Resolve existing stock by stock number even if the browser did not send item_id.
            // Lock the item so concurrent receipts cannot create a duplicate item.
            $stockNo = $attributes['stock_no'] ?? null;
            $selectedItem = !empty($attributes['item_id'])
                ? Item::whereKey($attributes['item_id'])->lockForUpdate()->firstOrFail()
                : ($stockNo ? Item::where('stock_no', $stockNo)->lockForUpdate()->first() : null);

            if ($selectedItem) {
                if (!$selectedItem->is_active || ($stockNo !== null && $selectedItem->stock_no !== $stockNo)) {
                    throw ValidationException::withMessages([
                        'stock_no' => 'The selected existing item does not match this stock number.',
                    ]);
                }
                if (isset($attributes['classification']) && $attributes['classification'] !== $selectedItem->classification) {
                    throw ValidationException::withMessages([
                        'classification' => 'Classification cannot be changed while adding stock to an existing item.',
                    ]);
                }
            }

            $item = $selectedItem ?? Item::create(Arr::only($attributes, [
                'stock_no', 'name', 'description', 'brand', 'quality', 'supplier',
                'category_id', 'classification', 'unit_of_measure',
            ]));

            $itemDetails = Arr::only($attributes, ['brand', 'quality', 'supplier']);
            if ($itemDetails !== []) {
                $item->update($itemDetails);
            }

            $attributes['item_id'] = $item->id;
            $quantity = (float) $attributes['quantity'];

            $inventory = Inventory::lockForUpdate()->firstOrCreate(
                ['item_id' => $attributes['item_id']],
                ['quantity_on_hand' => 0, 'quantity_reserved' => 0]
            );

            $previousQuantity = (float) $inventory->quantity_on_hand;
            $inventory->increment('quantity_on_hand', $quantity);
            if (array_key_exists('location', $attributes)) {
                $inventory->update(['location' => $attributes['location']]);
            }

            $lot = null;
            if (!empty($attributes['lot_no']) || !empty($attributes['expiration_date']) || !empty($attributes['unit_cost'])) {
                $lot = InventoryLot::create([
                    'item_id' => $attributes['item_id'],
                    'lot_no' => $attributes['lot_no'] ?? null,
                    'received_date' => $attributes['received_date'],
                    'expiration_date' => $attributes['expiration_date'] ?? null,
                    'unit_cost' => $attributes['unit_cost'] ?? null,
                    'quantity_on_hand' => $quantity,
                    'is_active' => true,
                ]);
            }

            InventoryTransaction::create([
                'item_id' => $attributes['item_id'],
                'inventory_lot_id' => $lot?->id,
                'type' => 'receipt',
                'quantity' => $quantity,
                'unit_cost' => $attributes['unit_cost'] ?? null,
                'reference_type' => 'inventory_receipt',
                'performed_by' => $performedBy->id,
                'reason' => $attributes['reason'] ?? null,
                'occurred_at' => now(),
            ]);

            $inventory = $inventory->fresh('item');
            $this->audit->record($performedBy, 'received', 'inventory', $inventory, [
                'quantity_on_hand' => $previousQuantity,
            ], [
                'item_id' => $inventory->item_id,
                'quantity_received' => $quantity,
                'quantity_on_hand' => $inventory->quantity_on_hand,
                'lot_id' => $lot?->id,
            ], $attributes['reason'] ?? null, $ipAddress, $userAgent);

            return $inventory;
        });
    }

    public function stockCard(int $itemId)
    {
        return InventoryTransaction::query()
            ->where('item_id', $itemId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }
}

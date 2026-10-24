<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogItemStockService
{
    public function __construct(
        private readonly CatalogManagementService $catalog,
        private readonly InventoryService $inventory,
    ) {}

    public function save(array $input, User $actor, ?string $ip = null, ?string $agent = null): string
    {
        return DB::transaction(function () use ($input, $actor, $ip, $agent): string {
            $quantity = (float) $input['quantity'];
            $existing = Item::where('stock_no', $input['stock_no'])->lockForUpdate()->first();
            $attributes = collect($input)->except(['quantity', 'confirm_existing'])->all();

            if ($existing) {
                if ($existing->classification !== 'consumable' || $input['classification'] !== 'consumable') {
                    throw ValidationException::withMessages(['stock_no' => 'Existing accountable items cannot receive stock through Add Item.']);
                }
                if (!$existing->is_active) {
                    throw ValidationException::withMessages(['stock_no' => 'This item is inactive. Activate it before adding stock.']);
                }
                if (empty($input['confirm_existing'])) {
                    throw ValidationException::withMessages(['confirm_existing' => 'Confirm the existing item before adding quantity.']);
                }
                if ($quantity <= 0) {
                    throw ValidationException::withMessages(['quantity' => 'Quantity to add must be greater than zero.']);
                }
                $item = $this->catalog->update($existing, $attributes, $actor, $ip, $agent);
                $message = 'Existing item details updated and quantity added successfully.';
            } else {
                $item = $this->catalog->create(new Item(), $attributes, $actor, $ip, $agent);
                $message = 'Item created and initial stock saved.';
            }

            if ($quantity > 0) {
                if ($item->classification !== 'consumable') {
                    throw ValidationException::withMessages(['quantity' => 'Automatic stock addition is for consumable items only. Enter 0 for accountable items.']);
                }
                if (!$actor->hasPermission('manage_inventory')) {
                    throw ValidationException::withMessages(['quantity' => 'You need inventory management permission to add stock.']);
                }
                $this->inventory->receive([
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'received_date' => now()->toDateString(),
                    'lot_no' => null,
                    'expiration_date' => null,
                    'unit_cost' => null,
                    'reason' => $existing ? 'Quantity added via Add Item form' : 'Initial stock via Add Item form',
                ], $actor, $ip, $agent);
            }
            return $message;
        }, 3);
    }
}

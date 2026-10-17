<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLot extends Model
{
    protected $fillable = ['item_id', 'lot_no', 'received_date', 'expiration_date', 'unit_cost', 'quantity_on_hand', 'supplier_id', 'is_active'];
    protected function casts(): array { return ['received_date' => 'date', 'expiration_date' => 'date', 'unit_cost' => 'decimal:2', 'quantity_on_hand' => 'decimal:3', 'is_active' => 'boolean']; }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}

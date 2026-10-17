<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalInventoryItem extends Model
{
    protected $fillable = [
        'physical_inventory_id', 'item_id', 'accountable_item_id', 'article', 'description', 'date_acquired',
        'old_property_no', 'new_property_no', 'unit_of_measure', 'unit_value', 'quantity_per_property_card',
        'quantity_per_physical_count', 'shortage_quantity', 'overage_quantity', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'unit_value' => 'decimal:2',
            'quantity_per_property_card' => 'decimal:3',
            'quantity_per_physical_count' => 'decimal:3',
            'shortage_quantity' => 'decimal:3',
            'overage_quantity' => 'decimal:3',
        ];
    }

    public function physicalInventory(): BelongsTo { return $this->belongsTo(PhysicalInventory::class); }

    public function item(): BelongsTo { return $this->belongsTo(Item::class); }

    public function accountableItem(): BelongsTo { return $this->belongsTo(AccountableItem::class); }
}
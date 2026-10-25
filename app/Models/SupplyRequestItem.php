<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyRequestItem extends Model
{
    protected $fillable = [
        'supply_request_id', 'item_id', 'confirmed_item_id', 'requested_quantity', 'confirmed_quantity',
        'issued_quantity', 'unavailable_quantity', 'reserved_quantity', 'unit_price_snapshot', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:3', 'confirmed_quantity' => 'decimal:3',
            'issued_quantity' => 'decimal:3', 'unavailable_quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3',
            'unit_price_snapshot' => 'decimal:2',
        ];
    }

    public function supplyRequest(): BelongsTo { return $this->belongsTo(SupplyRequest::class); }

    public function item(): BelongsTo { return $this->belongsTo(Item::class); }

    public function confirmedItem(): BelongsTo { return $this->belongsTo(Item::class, 'confirmed_item_id'); }
}

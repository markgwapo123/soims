<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RisItem extends Model
{
    protected $fillable = [
        'ris_id', 'item_id', 'supply_request_item_id', 'stock_no_snapshot', 'unit_snapshot', 'description_snapshot',
        'requested_quantity', 'available_quantity', 'issue_quantity', 'not_available_quantity',
        'unit_price', 'amount', 'stock_available', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:3', 'available_quantity' => 'decimal:3',
            'issue_quantity' => 'decimal:3', 'not_available_quantity' => 'decimal:3',
            'unit_price' => 'decimal:2', 'amount' => 'decimal:2', 'stock_available' => 'boolean',
        ];
    }

    public function ris(): BelongsTo { return $this->belongsTo(Ris::class); }

    public function item(): BelongsTo { return $this->belongsTo(Item::class); }

    public function availability(): string
    {
        return match (true) {
            (float) $this->issue_quantity <= 0 => 'None',
            (float) $this->not_available_quantity > 0 => 'Partial',
            default => 'Full',
        };
    }
}

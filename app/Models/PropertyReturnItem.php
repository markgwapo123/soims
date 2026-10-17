<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyReturnItem extends Model
{
    protected $fillable = [
        'property_return_id', 'accountable_item_id', 'property_no_snapshot', 'description_snapshot',
        'quantity', 'unit_value', 'remarks',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_value' => 'decimal:2'];
    }

    public function propertyReturn(): BelongsTo { return $this->belongsTo(PropertyReturn::class); }

    public function accountableItem(): BelongsTo { return $this->belongsTo(AccountableItem::class); }
}
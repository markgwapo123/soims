<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $fillable = ['item_id', 'location', 'quantity_on_hand', 'quantity_reserved', 'last_counted_at'];
    protected function casts(): array { return ['quantity_on_hand' => 'decimal:3', 'quantity_reserved' => 'decimal:3', 'last_counted_at' => 'datetime']; }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function getQuantityAvailableAttribute(): string { return number_format((float) $this->quantity_on_hand - (float) $this->quantity_reserved, 3, '.', ''); }
}

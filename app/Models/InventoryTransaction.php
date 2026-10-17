<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = ['item_id', 'inventory_lot_id', 'type', 'quantity', 'unit_cost', 'reference_type', 'reference_id', 'performed_by', 'reason', 'occurred_at'];
    protected function casts(): array { return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'occurred_at' => 'datetime']; }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function lot(): BelongsTo { return $this->belongsTo(InventoryLot::class, 'inventory_lot_id'); }
    public function performer(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}

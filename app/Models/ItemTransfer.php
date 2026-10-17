<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTransfer extends Model
{
    protected $fillable = [
        'accountable_item_id', 'from_assignment_id', 'to_assignment_id', 'from_location', 'to_location',
        'transferred_by', 'reason', 'transferred_at',
    ];

    protected function casts(): array
    {
        return ['transferred_at' => 'datetime'];
    }

    public function accountableItem(): BelongsTo { return $this->belongsTo(AccountableItem::class); }

    public function fromAssignment(): BelongsTo { return $this->belongsTo(ItemAssignment::class, 'from_assignment_id'); }

    public function toAssignment(): BelongsTo { return $this->belongsTo(ItemAssignment::class, 'to_assignment_id'); }

    public function transferredBy(): BelongsTo { return $this->belongsTo(User::class, 'transferred_by'); }
}
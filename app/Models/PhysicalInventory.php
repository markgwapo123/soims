<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhysicalInventory extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'physical_inventory_uuid', 'inventory_no', 'conducted_by', 'department_id', 'office_id',
        'conducted_at', 'status', 'remarks',
    ];

    protected function casts(): array { return ['conducted_at' => 'datetime']; }

    public function conductor(): BelongsTo { return $this->belongsTo(User::class, 'conducted_by'); }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }

    public function items(): HasMany { return $this->hasMany(PhysicalInventoryItem::class); }
}
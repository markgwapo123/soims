<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountableItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_id', 'property_no', 'barcode', 'serial_no', 'date_acquired', 'unit_value', 'status',
        'current_department_id', 'current_office_id', 'current_location', 'current_user_id', 'received_at',
    ];

    protected function casts(): array
    {
        return ['date_acquired' => 'date', 'unit_value' => 'decimal:2', 'received_at' => 'datetime'];
    }

    public function item(): BelongsTo { return $this->belongsTo(Item::class); }

    public function currentDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'current_department_id'); }

    public function currentOffice(): BelongsTo { return $this->belongsTo(Office::class, 'current_office_id'); }

    public function currentUser(): BelongsTo { return $this->belongsTo(User::class, 'current_user_id'); }

    public function assignments(): HasMany { return $this->hasMany(ItemAssignment::class)->orderByDesc('assigned_at')->orderByDesc('id'); }

    public function transfers(): HasMany { return $this->hasMany(ItemTransfer::class)->orderByDesc('transferred_at')->orderByDesc('id'); }

    public function propertyReturnItems(): HasMany { return $this->hasMany(PropertyReturnItem::class); }
}
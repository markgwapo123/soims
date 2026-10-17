<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'accountable_item_id', 'assigned_to_user_id', 'assigned_to_department_id', 'assigned_to_office_id',
        'location', 'assigned_by', 'assigned_at', 'released_at', 'release_reason', 'status',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function accountableItem(): BelongsTo { return $this->belongsTo(AccountableItem::class); }

    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to_user_id'); }

    public function assignedDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'assigned_to_department_id'); }

    public function assignedOffice(): BelongsTo { return $this->belongsTo(Office::class, 'assigned_to_office_id'); }

    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
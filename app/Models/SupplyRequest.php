<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyRequest extends Model
{
    public const TYPE_ACADEMIC = 'academic';
    public const TYPE_NON_ACADEMIC = 'non_academic';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DENIED = 'denied';

    protected $fillable = [
        'request_uuid', 'request_no', 'requestor_id', 'department_id', 'office_id', 'request_type',
        'purpose', 'status', 'submitted_at', 'confirmed_at', 'completed_at', 'rejected_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime', 'confirmed_at' => 'datetime',
            'completed_at' => 'datetime', 'rejected_at' => 'datetime',
        ];
    }

    public function requestor(): BelongsTo { return $this->belongsTo(User::class, 'requestor_id'); }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }

    public function items(): HasMany { return $this->hasMany(SupplyRequestItem::class); }

    public function approvals(): HasMany { return $this->hasMany(RequestApproval::class)->orderBy('decided_at'); }

    public function modifications(): HasMany { return $this->hasMany(RequestModification::class)->orderBy('modified_at')->orderBy('id'); }
}

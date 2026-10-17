<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyReturn extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const PURPOSE_REPAIR = 'repair';
    public const PURPOSE_DISPOSAL = 'disposal';
    public const PURPOSE_RETURNED_TO_STOCK = 'returned_to_stock';
    public const PURPOSE_OTHER = 'other';

    protected $fillable = [
        'property_return_uuid', 'prs_no', 'document_no', 'issue_no', 'revision_no', 'office_name', 'purpose',
        'received_from', 'returned_to', 'end_user', 'certification', 'returned_by', 'received_at', 'status',
    ];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function items(): HasMany { return $this->hasMany(PropertyReturnItem::class); }

    public function returner(): BelongsTo { return $this->belongsTo(User::class, 'returned_by'); }
}
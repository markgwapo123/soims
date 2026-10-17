<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ris extends Model
{
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_RELEASED = 'released';

    protected $table = 'ris';

    protected $fillable = [
        'ris_uuid', 'ris_no', 'revision_no', 'supply_request_id', 'entity_name', 'fund_cluster', 'division', 'office',
        'responsibility_center_code', 'purpose', 'requested_by', 'approved_by', 'issued_by', 'received_by',
        'received_printed_name', 'received_designation', 'issued_at', 'received_at', 'status',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function supplyRequest(): BelongsTo { return $this->belongsTo(SupplyRequest::class); }

    public function items(): HasMany { return $this->hasMany(RisItem::class); }

    public function release(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Release::class); }

    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }

    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }

    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}

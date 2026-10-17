<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestApproval extends Model
{
    protected $fillable = ['supply_request_id', 'approver_id', 'role_snapshot', 'decision', 'comments', 'decided_at'];

    protected function casts(): array { return ['decided_at' => 'datetime']; }

    public function supplyRequest(): BelongsTo { return $this->belongsTo(SupplyRequest::class); }

    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }
}

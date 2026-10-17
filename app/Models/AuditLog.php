<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'role_snapshot', 'action', 'module', 'record_type', 'record_id',
        'previous_value', 'new_value', 'reason', 'ip_address', 'user_agent', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['previous_value' => 'array', 'new_value' => 'array', 'occurred_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
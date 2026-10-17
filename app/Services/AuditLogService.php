<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        User $actor,
        string $action,
        string $module,
        Model $record,
        ?array $previous,
        array $new,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor->id,
            'role_snapshot' => $actor->roles()->where('roles.is_active', true)->wherePivotNull('revoked_at')->pluck('roles.key')->join(','),
            'action' => $action,
            'module' => $module,
            'record_type' => $record::class,
            'record_id' => $record->getKey(),
            'previous_value' => $previous,
            'new_value' => $new,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => now(),
        ]);
    }
}
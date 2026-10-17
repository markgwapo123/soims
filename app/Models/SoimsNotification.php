<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

// Maps the custom `notifications` table; separate from Laravel's built-in database notifications.
class SoimsNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['user_id', 'role_key', 'type', 'title', 'message', 'related_type', 'related_id', 'read_at'];

    protected function casts(): array { return ['read_at' => 'datetime']; }

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_reads', 'notification_id', 'user_id')
            ->withPivot('read_at')->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roleKeys = $user->roles()->where('roles.is_active', true)->wherePivotNull('revoked_at')->pluck('roles.key');

        return $query->where(function (Builder $query) use ($user, $roleKeys): void {
            $query->where('user_id', $user->id);
            if ($roleKeys->isNotEmpty()) {
                $query->orWhereIn('role_key', $roleKeys);
            }
        });
    }
}

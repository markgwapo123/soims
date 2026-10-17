<?php

namespace App\Services;

use App\Models\SoimsNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class NotificationService
{
    public function notificationsFor(User $user): LengthAwarePaginator
    {
        return SoimsNotification::visibleTo($user)
            ->with(['readByUsers' => fn ($query) => $query->where('users.id', $user->id)])
            ->latest('created_at')->paginate(20);
    }

    public function unreadCount(User $user): int
    {
        return $this->unreadQuery($user)->count();
    }

    public function markRead(SoimsNotification $notification, User $user): void
    {
        abort_unless(SoimsNotification::visibleTo($user)->whereKey($notification->id)->exists(), 404);

        DB::table('notification_reads')->updateOrInsert(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function markAllRead(User $user): int
    {
        $unread = $this->unreadQuery($user)->pluck('notifications.id');
        if ($unread->isEmpty()) {
            return 0;
        }

        $now = now();
        DB::table('notification_reads')->insertOrIgnore($unread->map(fn ($id) => [
            'notification_id' => $id,
            'user_id' => $user->id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        return $unread->count();
    }

    public function relatedUrl(SoimsNotification $notification, User $user): ?string
    {
        if (! $notification->related_type || ! $notification->related_id) {
            return null;
        }

        if ($notification->related_type === \App\Models\SupplyRequest::class) {
            $request = \App\Models\SupplyRequest::find($notification->related_id);
            return $request && $request->requestor_id === $user->id && $user->hasPermission('submit_supply_requests')
                ? route('requests.show', $request)
                : null;
        }

        if ($notification->related_type === \App\Models\Ris::class) {
            $ris = \App\Models\Ris::with('supplyRequest')->find($notification->related_id);
            $request = $ris?->supplyRequest;
            return $request && $request->requestor_id === $user->id && $user->hasPermission('submit_supply_requests')
                ? route('requests.show', $request)
                : null;
        }

        return null;
    }

    private function unreadQuery(User $user): Builder
    {
        return SoimsNotification::visibleTo($user)
            ->whereNull('read_at')
            ->whereDoesntHave('readByUsers', fn (Builder $query) => $query->where('users.id', $user->id));
    }
}
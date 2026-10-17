<?php

namespace App\Http\Controllers;

use App\Models\SoimsNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $this->notifications->notificationsFor($user);
        $notifications->getCollection()->each(function (SoimsNotification $notification) use ($user): void {
            $notification->setAttribute('is_read_by_viewer', $notification->readByUsers->isNotEmpty() || $notification->read_at !== null);
            $notification->setAttribute('related_url', $this->notifications->relatedUrl($notification, $user));
        });

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $this->notifications->unreadCount($user),
        ]);
    }

    public function read(Request $request, SoimsNotification $notification): RedirectResponse
    {
        $this->notifications->markRead($notification, $request->user());

        return back()->with('status', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $count = $this->notifications->markAllRead($request->user());

        return back()->with('status', $count.' notification'.($count === 1 ? '' : 's').' marked as read.');
    }
}
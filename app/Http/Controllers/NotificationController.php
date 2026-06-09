<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        if ($notification->notifiable_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->getUnreadNotificationsCount();

        return response()->json(['count' => $count]);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * The top-bar bell feed: the signed-in user's unread count and
     * latest notifications, polled by every page.
     */
    public function feed(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(8)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Security Alert',
                'detail' => $notification->data['detail'] ?? '',
                'severity' => $notification->data['severity_label'] ?? '',
                'badge' => $notification->data['badge'] ?? 'badge-muted',
                'time' => $notification->created_at->diffForHumans(),
                'read' => $notification->read_at !== null,
            ])->values(),
        ]);
    }

    /**
     * Mark one of the signed-in user's notifications as read
     * (clicked in the dropdown).
     */
    public function markRead(string $id): JsonResponse
    {
        auth()->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark every unread notification of the signed-in user as read.
     */
    public function markAllRead(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}

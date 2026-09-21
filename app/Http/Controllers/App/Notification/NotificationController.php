<?php

namespace App\Http\Controllers\App\Notification;

use App\Enums\NotificationCategoryEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $user->notifications();

        // Filter by category enum
        $filter = $request->input('filter', 'all');
        if ($filter !== 'all' && in_array($filter, NotificationCategoryEnum::values(), true)) {
            $query->where('data->category', $filter);
        }

        // Optional filter by outlet
        if ($request->filled('outlet_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('data->outlet_id')
                    ->orWhere('data->outlet_id', $request->input('outlet_id'));
            });
        }

        $notifications = $query->latest()->paginate(15);
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['success' => true]);
    }
}

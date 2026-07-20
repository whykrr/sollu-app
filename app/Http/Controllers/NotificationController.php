<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications.
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications();

        // Basic filtering support
        if ($request->filled('filter') && $request->filter !== 'all') {
            if ($request->filter === 'system') {
                $query->where('type', 'like', '%Csv%')
                      ->orWhere('type', 'like', '%Welcome%');
            } elseif ($request->filter === 'order') {
                $query->where('type', 'like', '%Order%')
                      ->orWhere('type', 'like', '%Transaction%');
            }
        }

        $notifications = $query->paginate(15);
        
        $unreadCount = $request->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the user.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['success' => true]);
    }
}

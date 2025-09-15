<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->notifications();

        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by read status if provided
        if ($request->filled('read')) {
            $query->where('read_at', $request->boolean('read') ? '!=' : '=', null);
        }

        $notifications = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total()
                ],
                'unread_count' => $user->notifications()->whereNull('read_at')->count()
            ]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $user = Auth::user();
        
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if ($notification->read_at) {
            return response()->json([
                'success' => false,
                'message' => 'Notification is already read'
            ], 400);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => [
                'notification' => $notification->fresh()
            ]
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
}

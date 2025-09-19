<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    /**
     * Get all subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with(['user', 'plan']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = $request->input('per_page', 20);
        $subscriptions = $query->latest()->paginate(min($perPage, 100));

        return response()->json([
            'success' => true,
            'message' => 'اشتراک‌ها دریافت شد',
            'data' => [
                'subscriptions' => $subscriptions->items(),
                'pagination' => [
                    'current_page' => $subscriptions->currentPage(),
                    'per_page' => $subscriptions->perPage(),
                    'total' => $subscriptions->total(),
                    'last_page' => $subscriptions->lastPage(),
                    'has_more' => $subscriptions->hasMorePages(),
                ]
            ]
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    /**
     * Public sponsor profile (active sponsors only).
     */
    public function show(Request $request, Sponsor $sponsor)
    {
        if (! $sponsor->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor not found',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        $sponsor->load(['stories' => function ($query) {
            $query->published()
                ->select(['id', 'title', 'subtitle', 'image_url', 'cover_image_url', 'category_id', 'sponsor_id', 'status'])
                ->orderByDesc('published_at');
        }]);

        return response()->json([
            'success' => true,
            'data' => $sponsor->toPublicProfile(),
        ]);
    }
}

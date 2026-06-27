<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomeFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeFeedService $homeFeedService
    ) {}

    public function personalized(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $user = $request->user();
        $limit = (int) $request->get('limit', 10);

        $sections = $this->homeFeedService->getPersonalizedSections($user, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'sections' => $sections,
                'user' => [
                    'name' => $user->name,
                    'account_type' => $user->account_type ?? 'child',
                    'onboarding_completed' => (bool) $user->onboarding_completed,
                ],
            ],
        ]);
    }
}

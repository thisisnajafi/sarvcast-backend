<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchHistoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:200',
            'result_count' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $query = trim($validated['query']);

        $existing = SearchHistory::query()
            ->where('user_id', $user->id)
            ->where('query', $query)
            ->where('searched_at', '>=', now()->subMinutes(5))
            ->latest('searched_at')
            ->first();

        if ($existing) {
            $existing->update([
                'result_count' => $validated['result_count'] ?? $existing->result_count,
                'searched_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => ['id' => $existing->id, 'query' => $existing->query],
            ]);
        }

        $entry = SearchHistory::create([
            'user_id' => $user->id,
            'query' => $query,
            'result_count' => $validated['result_count'] ?? 0,
            'searched_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['id' => $entry->id, 'query' => $entry->query],
        ], 201);
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 8), 20);

        $searches = SearchHistory::query()
            ->where('user_id', $request->user()->id)
            ->select('id', 'query', 'result_count', 'clicked_story_id', 'searched_at')
            ->orderByDesc('searched_at')
            ->limit($limit * 3)
            ->get()
            ->unique('query')
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'data' => ['searches' => $searches],
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 20);
        $days = (int) $request->get('days', 7);

        $trending = SearchHistory::query()
            ->where('searched_at', '>=', now()->subDays($days))
            ->select('query', DB::raw('COUNT(*) as search_count'))
            ->groupBy('query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['trending' => $trending],
        ]);
    }

    public function recordClick(Request $request, SearchHistory $searchHistory): JsonResponse
    {
        if ($searchHistory->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }

        $validated = $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
        ]);

        Story::query()->published()->findOrFail($validated['story_id']);

        $searchHistory->update([
            'clicked_story_id' => $validated['story_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'کلیک ثبت شد',
        ]);
    }
}

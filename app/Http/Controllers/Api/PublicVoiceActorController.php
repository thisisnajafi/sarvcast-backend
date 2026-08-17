<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserResumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicVoiceActorController extends Controller
{
    public function __construct(
        private readonly UserResumeService $resumes,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->input('per_page', 12) ?: 12));
        $search = trim((string) $request->input('q', $request->input('search', '')));

        $query = $this->resumes->talentDirectoryQuery()->inRandomOrder();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(
            fn (User $user) => $this->resumes->listingItem($user)
        )->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $user): JsonResponse
    {
        $model = User::query()->with('resume')->find($user);
        if (! $model || ! $this->resumes->appearsInTalentDirectory($model)) {
            return response()->json([
                'success' => false,
                'message' => 'رزومه عمومی یافت نشد.',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        $works = $this->resumes->publishedWorksPayload($model);
        $includeResume = (bool) ($model->resume?->is_public);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                [
                    'user' => $this->resumes->publicUserFields($model, $includeResume),
                ],
                $works
            ),
        ]);
    }
}

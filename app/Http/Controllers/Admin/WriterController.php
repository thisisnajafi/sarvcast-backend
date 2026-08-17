<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Story;
use App\Models\User;
use App\Services\ContributorStoryAccessService;
use App\Services\UserStoryContributionService;
use Illuminate\Http\Request;

class WriterController extends Controller
{
    public function __construct(
        private readonly ContributorStoryAccessService $access,
    ) {}

    public function apiIndex(Request $request)
    {
        $this->assertCanViewWriters($request->user());

        $query = User::query()->where('role', User::ROLE_WRITER);

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('phone_number', 'like', '%'.$search.'%');
            });
        }

        $perPage = min(100, max(1, (int) $request->input('perPage', $request->input('per_page', 15)) ?: 15));
        $page = max(1, (int) $request->input('page', 1));

        $paginator = $query
            ->withCount(['storiesAsAuthor as authored_stories_count'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->getCollection()->transform(fn (User $user) => $this->formatWriter($user));

        return AdminApiResponse::paginated($paginator);
    }

    public function apiStats()
    {
        $this->assertCanViewWriters(request()->user());

        return AdminApiResponse::success([
            'total_stories' => Story::query()->count(),
            'stories_without_writer' => Story::query()->whereNull('author_id')->count(),
            'total_writers' => User::query()->where('role', User::ROLE_WRITER)->count(),
        ]);
    }

    public function apiCandidates(Request $request)
    {
        $this->assertCanViewWriters($request->user());

        $search = trim((string) $request->input('q', $request->input('search', '')));
        $query = User::query()
            ->where('role', '!=', User::ROLE_CHILD)
            ->whereIn('status', User::loginAllowedStatuses())
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('phone_number', 'like', '%'.$search.'%');
            });
        }

        $items = $query->limit(20)->get()->map(fn (User $user) => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'status' => $user->status,
            'needs_promote' => in_array($user->role, User::writerPromotableRoles(), true),
        ]);

        return AdminApiResponse::success($items);
    }

    public function apiShow(User $user)
    {
        $this->assertCanViewWriters(request()->user());

        if ($user->role !== User::ROLE_WRITER
            && $user->role !== User::ROLE_HEAD_WRITER
            && Story::query()->where('author_id', $user->id)->doesntExist()) {
            return response()->json([
                'success' => false,
                'message' => 'این کاربر نویسنده نیست.',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        $user->load('resume');
        $payload = $this->formatWriter($user);
        $payload['story_contributions'] = app(UserStoryContributionService::class)->summarizeForUser($user);
        if (app(\App\Services\UserResumeService::class)->canOwnResume($user)) {
            $payload['resume'] = $user->resume
                ? app(\App\Services\UserResumeService::class)->toAdminArray($user->resume)
                : null;
        }

        return AdminApiResponse::success($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatWriter(User $user): array
    {
        $authoredCount = $user->authored_stories_count
            ?? Story::query()->where('author_id', $user->id)->count();

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'status' => $user->status,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'authored_stories_count' => (int) $authoredCount,
        ];
    }

    private function assertCanViewWriters(?User $user): void
    {
        if (! $user || ! $this->access->canAssignStoryWriter($user)) {
            abort(response()->json([
                'success' => false,
                'message' => 'دسترسی به فهرست نویسندگان مجاز نیست.',
                'error' => 'FORBIDDEN',
            ], 403));
        }
    }
}

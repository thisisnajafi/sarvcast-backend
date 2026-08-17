<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\User;
use App\Services\ContributorStoryAccessService;
use App\Services\UserResumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResumeController extends Controller
{
    public function __construct(
        private readonly UserResumeService $resumes,
        private readonly ContributorStoryAccessService $access,
    ) {}

    public function apiIndex(Request $request): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $this->access->isFullAdmin($actor)) {
            return $this->forbidden('مشاهده فهرست رزومه‌ها فقط برای مدیران است.');
        }

        $query = User::query()
            ->whereIn('role', UserResumeService::eligibleRoles())
            ->with('resume');

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('phone_number', 'like', '%'.$search.'%');
            });
        }

        $role = trim((string) $request->input('role', ''));
        if ($role !== '' && in_array($role, UserResumeService::eligibleRoles(), true)) {
            $query->where('role', $role);
        }

        if ($request->has('is_public')) {
            $public = filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($public === true) {
                $query->whereHas('resume', fn ($q) => $q->where('is_public', true));
            } elseif ($public === false) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('resume')
                        ->orWhereHas('resume', fn ($r) => $r->where('is_public', false));
                });
            }
        }

        $perPage = min(100, max(1, (int) $request->input('perPage', $request->input('per_page', 15)) ?: 15));
        $paginator = $query->orderBy('first_name')->orderBy('last_name')->paginate($perPage);
        $paginator->getCollection()->transform(fn (User $user) => $this->adminListItem($user));

        return AdminApiResponse::paginated($paginator);
    }

    public function apiShowMe(Request $request): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->forbidden('نشست نامعتبر است.');
        }

        return $this->apiShow($request, $actor);
    }

    public function apiUpdateMe(Request $request): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            return $this->forbidden('نشست نامعتبر است.');
        }

        return $this->apiUpdate($request, $actor);
    }

    public function apiShow(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $this->canView($actor, $user)) {
            return $this->forbidden('مشاهده این رزومه مجاز نیست.');
        }
        if (! $this->resumes->canOwnResume($user)) {
            return response()->json([
                'success' => false,
                'message' => 'این کاربر رزومه ندارد.',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        $user->load('resume');
        $resume = $this->resumes->firstOrCreateDraft($user, $actor->id);
        $user->setRelation('resume', $resume);

        return AdminApiResponse::success($this->adminDetail($user, $resume, $actor));
    }

    public function apiUpdate(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User || ! $this->canEdit($actor, $user)) {
            return $this->forbidden('ویرایش این رزومه مجاز نیست.');
        }
        if (! $this->resumes->canOwnResume($user)) {
            return response()->json([
                'success' => false,
                'message' => 'این کاربر رزومه ندارد.',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        $allowDirectory = $this->access->isFullAdmin($actor);

        try {
            $normalized = $this->resumes->validateAndNormalize($request->all(), $allowDirectory);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'اطلاعات رزومه نامعتبر است.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user->load('resume');
        $resume = $this->resumes->firstOrCreateDraft($user, $actor->id);
        $resume = $this->resumes->applyUpdate($resume, $normalized, $actor->id);
        $user->setRelation('resume', $resume);

        return AdminApiResponse::success(
            $this->adminDetail($user, $resume, $actor),
            'رزومه ذخیره شد.'
        );
    }

    private function canView(User $actor, User $target): bool
    {
        if ($this->access->isFullAdmin($actor)) {
            return true;
        }

        return $actor->id === $target->id && $this->resumes->canOwnResume($actor);
    }

    private function canEdit(User $actor, User $target): bool
    {
        return $this->canView($actor, $target);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminListItem(User $user): array
    {
        $resume = $user->resume;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'status' => $user->status,
            'profile_image_url' => $user->profile_image_url,
            'is_public' => (bool) ($resume?->is_public),
            'show_in_talent_directory' => (bool) ($resume?->show_in_talent_directory),
            'headline' => $resume?->headline,
            'years_of_experience' => $resume?->years_of_experience,
            'updated_at' => $resume?->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminDetail(User $user, $resume, User $actor): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $this->access->isFullAdmin($actor) ? $user->phone_number : null,
            'role' => $user->role,
            'status' => $user->status,
            'bio' => $user->bio,
            'profile_image_url' => $user->profile_image_url,
            'background_photo_url' => $user->background_photo_url,
            'resume' => $this->resumes->toAdminArray($resume),
        ];
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'FORBIDDEN',
        ], 403);
    }
}

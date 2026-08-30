<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Story;
use App\Models\StoryImageAssistant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StoryImageAssistantAssignmentService
{
    public function __construct(
        private readonly ContributorStoryAccessService $access,
    ) {}

    private function tableReady(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('story_image_assistants');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForStory(Story $story): Collection
    {
        if (! $this->tableReady()) {
            return collect();
        }

        return StoryImageAssistant::query()
            ->with(['user:id,first_name,last_name,phone_number,role,profile_image_url'])
            ->where('story_id', $story->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (StoryImageAssistant $row) => $this->summarizeAssignment($row));
    }

    public function assign(User $actor, Story $story, int $userId, bool $promoteToImageAssistant, ?string $notes = null): StoryImageAssistant
    {
        if (! $this->tableReady()) {
            abort(response()->json([
                'success' => false,
                'message' => 'جدول دستیار تصویر هنوز روی سرور ایجاد نشده است. ابتدا migrate را اجرا کنید.',
                'error' => 'MIGRATION_REQUIRED',
            ], 503));
        }

        if (! $this->access->canAssignImageAssistant($actor)) {
            abort(response()->json([
                'success' => false,
                'message' => 'شما مجاز به اختصاص دستیار تصویر نیستید.',
                'error' => 'FORBIDDEN',
            ], 403));
        }

        $target = User::query()->find($userId);
        if (! $target) {
            throw ValidationException::withMessages([
                'user_id' => 'کاربر انتخاب شده معتبر نیست.',
            ]);
        }

        $this->assertTargetMayReceiveAssignment($target, $promoteToImageAssistant);

        if (in_array($target->role, User::imageAssistantPromotableRoles(), true)) {
            $target->applyLegacyRoleChange(User::ROLE_IMAGE_ASSISTANT);
            $this->ensureImageAssistantRbacRole($target);
        } elseif ($target->role !== User::ROLE_IMAGE_ASSISTANT && ! $target->hasRole(User::ROLE_IMAGE_ASSISTANT)) {
            // Staff who already have another panel role keep users.role, but get the RBAC role for permissions.
            if (in_array($target->role, User::imageAssistantAssignableRolesWithoutPromote(), true)) {
                $this->ensureImageAssistantRbacRole($target);
            }
        }

        $assignment = StoryImageAssistant::query()->updateOrCreate(
            [
                'story_id' => $story->id,
                'user_id' => $target->id,
            ],
            [
                'assigned_by' => $actor->id,
                'notes' => $notes,
            ]
        );

        return $assignment->load(['user:id,first_name,last_name,phone_number,role,profile_image_url']);
    }

    public function revoke(User $actor, Story $story, int $userId): void
    {
        if (! $this->tableReady()) {
            abort(response()->json([
                'success' => false,
                'message' => 'جدول دستیار تصویر هنوز روی سرور ایجاد نشده است. ابتدا migrate را اجرا کنید.',
                'error' => 'MIGRATION_REQUIRED',
            ], 503));
        }

        if (! $this->access->canAssignImageAssistant($actor)) {
            abort(response()->json([
                'success' => false,
                'message' => 'شما مجاز به لغو دستیار تصویر نیستید.',
                'error' => 'FORBIDDEN',
            ], 403));
        }

        StoryImageAssistant::query()
            ->where('story_id', $story->id)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function summarizeAssignment(StoryImageAssistant $row): array
    {
        $user = $row->user;

        return [
            'id' => $row->id,
            'story_id' => $row->story_id,
            'user_id' => $row->user_id,
            'assigned_by' => $row->assigned_by,
            'notes' => $row->notes,
            'created_at' => $row->created_at,
            'user' => $user ? [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => Story::displayNameForUser($user),
                'role' => $user->role,
                'profile_image_url' => $user->profile_image_url,
            ] : null,
        ];
    }

    private function assertTargetMayReceiveAssignment(User $target, bool $promoteToImageAssistant): void
    {
        if ($target->role === User::ROLE_CHILD) {
            throw ValidationException::withMessages([
                'user_id' => 'نمی‌توان نقش دستیار تصویر را به حساب کودک داد.',
            ]);
        }

        if (! in_array($target->status, User::loginAllowedStatuses(), true)) {
            throw ValidationException::withMessages([
                'user_id' => 'فقط کاربران فعال می‌توانند دستیار تصویر شوند.',
            ]);
        }

        if (in_array($target->role, User::imageAssistantAssignableRolesWithoutPromote(), true)) {
            return;
        }

        if (in_array($target->role, User::imageAssistantPromotableRoles(), true)) {
            if (! $promoteToImageAssistant) {
                throw ValidationException::withMessages([
                    'promote_to_image_assistant' => 'برای اختصاص به این کاربر باید نقش دستیار تصویر تأیید شود.',
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'user_id' => 'این کاربر واجد شرایط دستیار تصویر نیست.',
        ]);
    }

    private function ensureImageAssistantRbacRole(User $user): void
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_IMAGE_ASSISTANT],
            [
                'display_name' => 'دستیار تصویر',
                'description' => 'مشاهده پرامپت‌ها و مدیریت تایم‌لاین داستان‌های اختصاص‌یافته',
                'is_active' => true,
            ]
        );
        $user->assignRole($role);
    }
}

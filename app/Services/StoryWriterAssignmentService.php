<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Story;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StoryWriterAssignmentService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ContributorStoryAccessService $access,
    ) {}

    public function assign(User $actor, Story $story, int $userId, bool $promoteToWriter): Story
    {
        if (! $this->access->canAssignStoryWriter($actor)) {
            abort(response()->json([
                'success' => false,
                'message' => 'شما مجاز به واگذاری نویسنده نیستید.',
                'error' => 'FORBIDDEN',
            ], 403));
        }

        $target = User::query()->find($userId);
        if (! $target) {
            throw ValidationException::withMessages([
                'user_id' => 'کاربر انتخاب شده معتبر نیست.',
            ]);
        }

        $this->assertTargetMayReceiveStory($target, $promoteToWriter);

        if (in_array($target->role, User::writerPromotableRoles(), true)) {
            $target->applyLegacyRoleChange(User::ROLE_WRITER);
            $this->ensureWriterRbacRole($target);
        }

        $previousAuthorId = $story->author_id ? (int) $story->author_id : null;
        $story->update(['author_id' => $target->id]);
        $story->refresh()->load(['author', 'narrator']);

        if ($previousAuthorId && $previousAuthorId !== (int) $target->id) {
            $previous = User::query()->find($previousAuthorId);
            if ($previous) {
                $this->notifications->sendVoiceActorRemovalNotification($previous, 'story_writer', [
                    'story_id' => $story->id,
                    'story_title' => $story->title,
                ]);
            }
        }

        $this->notifications->sendVoiceActorAssignmentNotification($target, 'story_writer', [
            'story_id' => $story->id,
            'story_title' => $story->title,
        ]);

        return $story;
    }

    public function revoke(User $actor, Story $story): Story
    {
        if (! $this->access->canAssignStoryWriter($actor)) {
            abort(response()->json([
                'success' => false,
                'message' => 'شما مجاز به لغو نویسنده نیستید.',
                'error' => 'FORBIDDEN',
            ], 403));
        }

        $previousAuthorId = $story->author_id ? (int) $story->author_id : null;
        $story->update(['author_id' => null]);
        $story->refresh()->load(['author', 'narrator']);

        if ($previousAuthorId) {
            $previous = User::query()->find($previousAuthorId);
            if ($previous) {
                $this->notifications->sendVoiceActorRemovalNotification($previous, 'story_writer', [
                    'story_id' => $story->id,
                    'story_title' => $story->title,
                ]);
            }
        }

        return $story;
    }

    private function assertTargetMayReceiveStory(User $target, bool $promoteToWriter): void
    {
        if ($target->role === User::ROLE_CHILD) {
            throw ValidationException::withMessages([
                'user_id' => 'نمی‌توان نقش نویسنده را به حساب کودک داد.',
            ]);
        }

        if (! in_array($target->status, User::loginAllowedStatuses(), true)) {
            throw ValidationException::withMessages([
                'user_id' => 'فقط کاربران فعال می‌توانند نویسنده داستان شوند.',
            ]);
        }

        if (in_array($target->role, User::writerAssignableRolesWithoutPromote(), true)) {
            return;
        }

        if (in_array($target->role, User::writerPromotableRoles(), true)) {
            if (! $promoteToWriter) {
                throw ValidationException::withMessages([
                    'promote_to_writer' => 'برای واگذاری به این کاربر باید نقش نویسنده تأیید شود.',
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'user_id' => 'این کاربر واجد شرایط نویسندگی داستان نیست.',
        ]);
    }

    private function ensureWriterRbacRole(User $user): void
    {
        $role = Role::query()->firstOrCreate(
            ['name' => User::ROLE_WRITER],
            [
                'display_name' => 'نویسنده',
                'description' => 'مشاهده و ویرایش اسکریپت داستان‌های اختصاص‌یافته',
                'is_active' => true,
            ]
        );
        $user->assignRole($role);
    }
}

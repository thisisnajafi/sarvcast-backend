<?php

namespace App\Services;

use App\Models\Story;
use App\Models\StoryProductionFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ContributorStoryAccessService
{
    public function isFullAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)
            || $user->isSuperAdmin();
    }

    public function isContributor(?User $user): bool
    {
        if (! $user || $this->isFullAdmin($user)) {
            return false;
        }

        return $this->hasAnyAssignableStoryAccess($user);
    }

    public function mayAccessAdminPanel(User $user): bool
    {
        if (! in_array($user->status, User::loginAllowedStatuses(), true)) {
            return false;
        }

        if ($this->isFullAdmin($user)) {
            return true;
        }

        return $this->hasAnyAssignableStoryAccess($user);
    }

    public function hasAnyAssignableStoryAccess(User $user): bool
    {
        if ($user->role === User::ROLE_VOICE_ACTOR || $user->isVoiceActor()) {
            return true;
        }

        return Story::query()
            ->where(function (Builder $q) use ($user) {
                $q->where('author_id', $user->id)
                    ->orWhere('narrator_id', $user->id)
                    ->orWhereHas('characters', fn (Builder $c) => $c->where('voice_actor_id', $user->id));
            })
            ->exists();
    }

    public function canViewStory(User $user, Story $story): bool
    {
        if ($this->isFullAdmin($user)) {
            return true;
        }

        if ((int) $story->author_id === (int) $user->id) {
            return true;
        }

        if ((int) $story->narrator_id === (int) $user->id) {
            return true;
        }

        return $story->characters()->where('voice_actor_id', $user->id)->exists();
    }

    public function canEditScript(User $user, Story $story): bool
    {
        if ($this->isFullAdmin($user)) {
            return true;
        }

        return (int) $story->author_id === (int) $user->id;
    }

    public function canAccessPackage(User $user): bool
    {
        return $this->isFullAdmin($user);
    }

    /**
     * @return array<int, int>
     */
    public function accessibleStoryIds(User $user): array
    {
        if ($this->isFullAdmin($user)) {
            return Story::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return Story::query()
            ->where(function (Builder $q) use ($user) {
                $q->where('author_id', $user->id)
                    ->orWhere('narrator_id', $user->id)
                    ->orWhereHas('characters', fn (Builder $c) => $c->where('voice_actor_id', $user->id));
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function scopeStoriesForUser(Builder $query, User $user): Builder
    {
        if ($this->isFullAdmin($user)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('author_id', $user->id)
                ->orWhere('narrator_id', $user->id)
                ->orWhereHas('characters', fn (Builder $c) => $c->where('voice_actor_id', $user->id));
        });
    }

    public function resolveDbStoryIdFromEditorSlug(string $storySlug): ?int
    {
        $fromFile = StoryProductionFile::query()
            ->where('story_slug', $storySlug)
            ->whereNotNull('story_id')
            ->value('story_id');

        if ($fromFile) {
            return (int) $fromFile;
        }

        $fromAsset = \App\Models\StoryProductionAsset::query()
            ->where('story_slug', $storySlug)
            ->whereNotNull('story_id')
            ->value('story_id');

        return $fromAsset ? (int) $fromAsset : null;
    }

    public function canViewEditorStory(User $user, string $storySlug): bool
    {
        if ($this->isFullAdmin($user)) {
            return true;
        }

        $dbId = $this->resolveDbStoryIdFromEditorSlug($storySlug);
        if (! $dbId) {
            return false;
        }

        $story = Story::query()->find($dbId);

        return $story ? $this->canViewStory($user, $story) : false;
    }

    public function canEditEditorScript(User $user, string $storySlug): bool
    {
        if ($this->isFullAdmin($user)) {
            return true;
        }

        $dbId = $this->resolveDbStoryIdFromEditorSlug($storySlug);
        if (! $dbId) {
            return false;
        }

        $story = Story::query()->find($dbId);

        return $story ? $this->canEditScript($user, $story) : false;
    }

    /**
     * @return array<string, mixed>
     */
    public function accessPayload(User $user): array
    {
        $fullAdmin = $this->isFullAdmin($user);
        $authored = Story::query()->where('author_id', $user->id)->exists();
        $cast = Story::query()
            ->where(function (Builder $q) use ($user) {
                $q->where('narrator_id', $user->id)
                    ->orWhereHas('characters', fn (Builder $c) => $c->where('voice_actor_id', $user->id));
            })
            ->exists();

        return [
            'is_full_admin' => $fullAdmin,
            'is_contributor' => ! $fullAdmin && ($authored || $cast || $user->role === User::ROLE_VOICE_ACTOR),
            'can_view_assigned_stories' => $fullAdmin || $authored || $cast || $user->role === User::ROLE_VOICE_ACTOR,
            'can_edit_authored_scripts' => $fullAdmin || $authored,
            'can_access_story_package' => $fullAdmin,
        ];
    }

    /**
     * Synthetic permissions so the dashboard PermissionGate can hide admin-only actions.
     *
     * @return array<int, string>
     */
    public function contributorPermissions(User $user): array
    {
        if ($this->isFullAdmin($user)) {
            return [];
        }

        $perms = ['stories.read', 'story_editor.read', 'dashboard.view'];

        if (Story::query()->where('author_id', $user->id)->exists()) {
            $perms[] = 'story_editor.update';
        }

        return $perms;
    }
}

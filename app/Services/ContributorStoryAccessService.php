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

        // Admin OTP / panel login: only these roles (not parent/child/basic/etc.).
        return $this->isFullAdmin($user)
            || $user->role === User::ROLE_VOICE_ACTOR
            || $user->isVoiceActor();
    }

    /**
     * Whether the user may receive an admin-panel OTP SMS.
     * Same allowlist as panel login — no OTP for simple app roles.
     */
    public function mayReceiveAdminOtp(User $user): bool
    {
        return $this->mayAccessAdminPanel($user);
    }

    public function hasAnyAssignableStoryAccess(User $user): bool
    {
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

    /**
     * Normalize titles for fuzzy matching between DB stories and editor folders.
     */
    public function normalizeTitle(?string $title): string
    {
        $value = trim((string) $title);
        if ($value === '') {
            return '';
        }

        // Drop trailing " (English Name)" used in characters_and_objects.json
        if (preg_match('/^(.+?)\s*\([^)]*\)\s*$/u', $value, $m)) {
            $value = trim($m[1]);
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    public function titlesMatch(?string $a, ?string $b): bool
    {
        $left = $this->normalizeTitle($a);
        $right = $this->normalizeTitle($b);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        return str_contains($left, $right) || str_contains($right, $left);
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

        if ($fromAsset) {
            return (int) $fromAsset;
        }

        // Fallback when production rows are missing / unlinked: match DB title to editor meta.
        return $this->resolveDbStoryIdFromEditorMeta($storySlug);
    }

    /**
     * Match a story-editor folder slug to a DB story via Persian title / folder name.
     */
    public function resolveDbStoryIdFromEditorMeta(string $storySlug): ?int
    {
        $meta = app(StoryEditorRepository::class)->getStoryMetaForSlug($storySlug);
        if (! $meta) {
            return null;
        }

        $persian = trim((string) ($meta['name_persian'] ?? ''));
        if ($persian !== '') {
            $stories = Story::query()->get(['id', 'title']);
            foreach ($stories as $story) {
                if ($this->titlesMatch($persian, $story->title)) {
                    return (int) $story->id;
                }
            }
        }

        $folder = trim((string) ($meta['folder_name'] ?? ''));
        if ($folder !== '' && preg_match('/^\d+\s*[-–]\s*(.+)$/u', $folder, $m)) {
            $guess = trim($m[1]);
            if ($guess !== '') {
                $stories = Story::query()->get(['id', 'title']);
                foreach ($stories as $story) {
                    if ($this->titlesMatch($guess, $story->title)) {
                        return (int) $story->id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Filter story-editor list entries to stories the contributor may view.
     *
     * @param  array<int, array<string, mixed>>  $stories
     * @return array<int, array<string, mixed>>
     */
    public function filterEditorStoriesForUser(array $stories, User $user): array
    {
        if ($this->isFullAdmin($user)) {
            return $stories;
        }

        $accessibleIds = $this->accessibleStoryIds($user);
        if ($accessibleIds === []) {
            return [];
        }

        $repo = app(StoryEditorRepository::class);
        $allowedSlugs = [];
        foreach ($accessibleIds as $dbId) {
            $slug = $repo->findStorySlugByDbStoryId($dbId);
            if (is_string($slug) && $slug !== '') {
                $allowedSlugs[$slug] = true;
            }
        }

        // Also allow reverse matches (production slug / title) for any leftover editor folders.
        $accessibleFlip = array_fill_keys($accessibleIds, true);
        $slugs = array_values(array_filter(array_map(
            static fn (array $story) => (string) ($story['id'] ?? ''),
            $stories,
        )));

        $fromFiles = StoryProductionFile::query()
            ->whereIn('story_slug', $slugs)
            ->whereNotNull('story_id')
            ->pluck('story_id', 'story_slug');

        $fromAssets = \App\Models\StoryProductionAsset::query()
            ->whereIn('story_slug', $slugs)
            ->whereNotNull('story_id')
            ->pluck('story_id', 'story_slug');

        $titleToId = Story::query()
            ->whereIn('id', $accessibleIds)
            ->get(['id', 'title']);

        return array_values(array_filter($stories, function (array $story) use (
            $allowedSlugs,
            $accessibleFlip,
            $fromFiles,
            $fromAssets,
            $titleToId,
        ) {
            $slug = (string) ($story['id'] ?? '');
            if ($slug === '') {
                return false;
            }

            if (isset($allowedSlugs[$slug])) {
                return true;
            }

            $dbId = $fromFiles[$slug] ?? $fromAssets[$slug] ?? null;
            if ($dbId && isset($accessibleFlip[(int) $dbId])) {
                return true;
            }

            $persian = trim((string) ($story['name_persian'] ?? ''));
            $folder = trim((string) ($story['folder_name'] ?? ''));
            $folderTitle = '';
            if ($folder !== '' && preg_match('/^\d+\s*[-–]\s*(.+)$/u', $folder, $m)) {
                $folderTitle = trim($m[1]);
            }

            foreach ($titleToId as $row) {
                $id = (int) $row->id;
                if (! isset($accessibleFlip[$id])) {
                    continue;
                }
                if ($persian !== '' && $this->titlesMatch($persian, $row->title)) {
                    return true;
                }
                if ($folderTitle !== '' && $this->titlesMatch($folderTitle, $row->title)) {
                    return true;
                }
                if ($folder !== '' && $this->titlesMatch($folder, $row->title)) {
                    return true;
                }
            }

            return false;
        }));
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

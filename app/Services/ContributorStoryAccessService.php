<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\ImageTimeline;
use App\Models\Story;
use App\Models\StoryImageAssistant;
use App\Models\StoryProductionAsset;
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

    public function isHeadWriter(?User $user): bool
    {
        if (! $user || $this->isFullAdmin($user)) {
            return false;
        }

        return $user->isHeadWriter();
    }

    public function isWriterStaff(?User $user): bool
    {
        if (! $user || $this->isFullAdmin($user) || $this->isHeadWriter($user)) {
            return false;
        }

        return $user->isWriter();
    }

    public function isImageAssistantStaff(?User $user): bool
    {
        if (! $user || $this->isFullAdmin($user) || $this->isHeadWriter($user)) {
            return false;
        }

        return $user->isImageAssistant() || $this->hasImageAssistantAssignments($user);
    }

    public function canViewAllStories(?User $user): bool
    {
        return $this->isFullAdmin($user) || $this->isHeadWriter($user);
    }

    public function canAssignStoryWriter(?User $user): bool
    {
        return $this->isFullAdmin($user) || $this->isHeadWriter($user);
    }

    public function canAssignImageAssistant(?User $user): bool
    {
        return $this->isFullAdmin($user);
    }

    public function isContributor(?User $user): bool
    {
        if (! $user || $this->isFullAdmin($user) || $this->isHeadWriter($user)) {
            return false;
        }

        return $this->isWriterStaff($user)
            || $user->isVoiceActor()
            || $this->isImageAssistantStaff($user)
            || $this->hasAnyAssignableStoryAccess($user);
    }

    public function mayAccessAdminPanel(User $user): bool
    {
        if (! in_array($user->status, User::loginAllowedStatuses(), true)) {
            return false;
        }

        // Admin OTP / panel login: staff roles only (not parent/child/basic).
        return $this->isFullAdmin($user)
            || $this->isHeadWriter($user)
            || $this->isWriterStaff($user)
            || $user->role === User::ROLE_VOICE_ACTOR
            || $user->isVoiceActor()
            || $user->role === User::ROLE_IMAGE_ASSISTANT
            || $user->isImageAssistant()
            || $this->hasImageAssistantAssignments($user);
    }

    /**
     * Whether the user may receive an admin-panel OTP SMS.
     * Same allowlist as panel login — no OTP for simple app roles.
     */
    public function mayReceiveAdminOtp(User $user): bool
    {
        return $this->mayAccessAdminPanel($user);
    }

    public function hasImageAssistantAssignments(User $user): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('story_image_assistants')) {
            return false;
        }

        return StoryImageAssistant::query()->where('user_id', $user->id)->exists();
    }

    public function isAssignedImageAssistant(User $user, Story $story): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('story_image_assistants')) {
            return false;
        }

        return StoryImageAssistant::query()
            ->where('story_id', $story->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function hasAnyAssignableStoryAccess(User $user): bool
    {
        return Story::query()
            ->where(function (Builder $q) use ($user) {
                $q->where('author_id', $user->id)
                    ->orWhere('narrator_id', $user->id)
                    ->orWhereHas('characters', fn (Builder $c) => $c->where('voice_actor_id', $user->id))
                    ->orWhereHas('imageAssistants', fn (Builder $a) => $a->where('users.id', $user->id));
            })
            ->exists();
    }

    public function canViewStory(User $user, Story $story): bool
    {
        if ($this->canViewAllStories($user)) {
            return true;
        }

        if ($this->isAssignedImageAssistant($user, $story)) {
            return true;
        }

        if ((int) $story->author_id === (int) $user->id) {
            return true;
        }

        if ($this->isWriterStaff($user) && ! $user->isVoiceActor() && ! $this->isImageAssistantStaff($user)) {
            return false;
        }

        if ((int) $story->narrator_id === (int) $user->id) {
            return true;
        }

        return $story->characters()->where('voice_actor_id', $user->id)->exists();
    }

    public function canViewPrompts(User $user, Story $story): bool
    {
        if ($this->isFullAdmin($user)) {
            return true;
        }

        return $this->isAssignedImageAssistant($user, $story);
    }

    public function canManageTimeline(User $user, Story $story): bool
    {
        if ($this->isFullAdmin($user)) {
            return true;
        }

        return $this->isAssignedImageAssistant($user, $story);
    }

    public function canManageEpisodeTimeline(User $user, Episode $episode): bool
    {
        $story = $episode->relationLoaded('story')
            ? $episode->story
            : Story::query()->find($episode->story_id);

        if (! $story) {
            return false;
        }

        return $this->canManageTimeline($user, $story);
    }

    public function canManageImageTimelineRecord(User $user, ImageTimeline $timeline): bool
    {
        if ($this->isFullAdmin($user)) {
            return true;
        }

        $storyId = $timeline->story_id;
        if (! $storyId && $timeline->episode_id) {
            $storyId = Episode::query()->whereKey($timeline->episode_id)->value('story_id');
        }

        if (! $storyId) {
            return false;
        }

        $story = Story::query()->find($storyId);

        return $story ? $this->canManageTimeline($user, $story) : false;
    }

    public function canEditScript(User $user, Story $story): bool
    {
        if ($this->canViewAllStories($user)) {
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
        if ($this->canViewAllStories($user)) {
            return Story::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $query = Story::query();
        $this->scopeStoriesForUser($query, $user);

        return $query
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function imageAssistantStoryIds(User $user): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('story_image_assistants')) {
            return [];
        }

        return StoryImageAssistant::query()
            ->where('user_id', $user->id)
            ->pluck('story_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function scopeStoriesForUser(Builder $query, User $user): Builder
    {
        if ($this->canViewAllStories($user)) {
            return $query;
        }

        $imageStoryIds = $this->imageAssistantStoryIds($user);

        if ($this->isWriterStaff($user) && ! $user->isVoiceActor() && ! $this->isImageAssistantStaff($user)) {
            return $query->where(function (Builder $q) use ($user, $imageStoryIds) {
                $q->where('author_id', $user->id);
                if ($imageStoryIds !== []) {
                    $q->orWhereIn('id', $imageStoryIds);
                }
            });
        }

        if ($this->isImageAssistantStaff($user) && ! $this->isWriterStaff($user) && ! $user->isVoiceActor()) {
            if ($imageStoryIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('id', $imageStoryIds);
        }

        // Voice actors (and hybrid cast/author): assigned cast + authored + image-assistant stories.
        return $query->where(function (Builder $q) use ($user, $imageStoryIds) {
            $q->where('author_id', $user->id)
                ->orWhere('narrator_id', $user->id)
                ->orWhereHas('characters', fn (Builder $c) => $c->where('voice_actor_id', $user->id));
            if ($imageStoryIds !== []) {
                $q->orWhereIn('id', $imageStoryIds);
            }
        });
    }

    public function scopeEpisodesForUser(Builder $query, User $user): Builder
    {
        if ($this->canViewAllStories($user)) {
            return $query;
        }

        $storyIds = $this->accessibleStoryIds($user);
        if ($storyIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('story_id', $storyIds);
    }

    public function scopeTimelinesForUser(Builder $query, User $user): Builder
    {
        if ($this->isFullAdmin($user)) {
            return $query;
        }

        $storyIds = $this->imageAssistantStoryIds($user);
        if ($storyIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($storyIds) {
            $q->whereIn('story_id', $storyIds)
                ->orWhereHas('episode', fn (Builder $e) => $e->whereIn('story_id', $storyIds));
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

        $fromAsset = StoryProductionAsset::query()
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
        if ($this->canViewAllStories($user)) {
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

        $fromAssets = StoryProductionAsset::query()
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
        if ($this->canViewAllStories($user)) {
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
        if ($this->canViewAllStories($user)) {
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
        $headWriter = $this->isHeadWriter($user);
        $writerStaff = $this->isWriterStaff($user);
        // Avoid querying story_image_assistants for full admins (and before migrate).
        $hasImageAssignments = $fullAdmin ? false : $this->hasImageAssistantAssignments($user);
        $imageAssistant = $fullAdmin || $headWriter
            ? false
            : ($user->isImageAssistant() || $hasImageAssignments);
        $authored = Story::query()->where('author_id', $user->id)->exists();
        $cast = Story::query()
            ->where(function (Builder $q) use ($user) {
                $q->where('narrator_id', $user->id)
                    ->orWhereHas('characters', fn (Builder $c) => $c->where('voice_actor_id', $user->id));
            })
            ->exists();
        $voiceActor = $user->isVoiceActor();

        return [
            'is_full_admin' => $fullAdmin,
            'is_contributor' => ! $fullAdmin && ! $headWriter && ($writerStaff || $authored || $cast || $voiceActor || $imageAssistant || $hasImageAssignments),
            'is_head_writer' => $headWriter,
            'is_writer' => $writerStaff,
            'is_voice_actor' => ! $fullAdmin && ! $headWriter && $voiceActor,
            'is_image_assistant' => $imageAssistant || $hasImageAssignments,
            'can_view_assigned_stories' => $fullAdmin || $headWriter || $writerStaff || $authored || $cast || $voiceActor || $imageAssistant || $hasImageAssignments,
            'can_view_all_stories' => $fullAdmin || $headWriter,
            'can_edit_authored_scripts' => $fullAdmin || $headWriter || $authored,
            'can_edit_all_scripts' => $fullAdmin || $headWriter,
            'can_assign_story_writers' => $fullAdmin || $headWriter,
            'can_assign_image_assistants' => $fullAdmin,
            'can_view_prompts' => $fullAdmin || $hasImageAssignments || $imageAssistant,
            'can_manage_timeline' => $fullAdmin || $hasImageAssignments || $imageAssistant,
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

        if ($this->isHeadWriter($user)) {
            return [
                'dashboard.view',
                'stories.read',
                'stories.assign_writer',
                'story_editor.read',
                'story_editor.update',
                'writers.view',
                'writers.assign',
                'writers.revoke',
            ];
        }

        $perms = ['stories.read', 'dashboard.view'];

        if ($this->isImageAssistantStaff($user) || $this->hasImageAssistantAssignments($user)) {
            $perms = array_merge($perms, [
                'prompts.read',
                'timeline.read',
                'timeline.update',
                'media.read',
                'media.create',
            ]);
        }

        if ($this->isWriterStaff($user) || $user->isVoiceActor() || Story::query()->where('author_id', $user->id)->exists()) {
            $perms[] = 'story_editor.read';
        }

        if (Story::query()->where('author_id', $user->id)->exists()) {
            $perms[] = 'story_editor.update';
        }

        return array_values(array_unique($perms));
    }
}

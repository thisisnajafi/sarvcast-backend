<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PlayHistory;
use App\Models\SearchHistory;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HomeFeedService
{
    public function __construct(
        private readonly RecommendationService $recommendationService
    ) {}

    /**
     * Build personalized home sections for the authenticated user.
     */
    public function getPersonalizedSections(User $user, int $limit = 10): array
    {
        $sections = [];
        $sectionOrder = $this->sectionOrderForAccountType($user->account_type ?? 'child');

        foreach ($sectionOrder as $type) {
            $section = match ($type) {
                'featured' => $this->buildFeaturedSection($user, $limit),
                'for_you' => $this->buildForYouSection($user, $limit),
                'by_age' => $this->buildByAgeSection($user, $limit),
                'by_category' => $this->buildByCategorySection($user, $limit),
                'based_on_interests' => $this->buildBasedOnInterestsSection($user, $limit),
                'educational' => $this->buildEducationalSection($user, $limit),
                'new' => $this->buildNewSection($user, $limit),
                default => null,
            };

            if ($section !== null && !empty($section['stories'])) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    private function sectionOrderForAccountType(string $accountType): array
    {
        return match ($accountType) {
            'parent' => ['featured', 'educational', 'for_you', 'by_age', 'by_category', 'based_on_interests', 'new'],
            'shared' => ['featured', 'for_you', 'by_age', 'by_category', 'based_on_interests', 'new'],
            default => ['featured', 'for_you', 'by_age', 'by_category', 'based_on_interests', 'new'],
        };
    }

    private function baseQuery(User $user): Builder
    {
        $query = Story::query()
            ->with(['category', 'director', 'narrator', 'author', 'characters.voiceActor'])
            ->withSum('episodes', 'play_count')
            ->published();

        return $this->applyAgeGroupFilter($query, $user->age_group);
    }

    private function applyAgeGroupFilter(Builder $query, ?string $ageGroup): Builder
    {
        $groups = User::storyAgeGroupsForFilter($ageGroup);
        if ($groups === null) {
            return $query;
        }

        return $query->whereIn('age_group', $groups);
    }

    private function applyGenderBoost(Builder $query, ?string $gender): Builder
    {
        if ($gender === null || $gender === 'unspecified') {
            return $query->orderByDesc('play_count');
        }

        $affinityTags = match ($gender) {
            'female' => ['female', 'girl', 'دختر', 'دخترانه'],
            'male' => ['male', 'boy', 'پسر', 'پسرانه'],
            default => [],
        };

        if (empty($affinityTags)) {
            return $query->orderByDesc('play_count');
        }

        $cases = collect($affinityTags)
            ->map(fn ($tag) => "WHEN tags LIKE '%\"{$tag}\"%' OR tags LIKE '%{$tag}%' THEN 1")
            ->implode(' ');

        return $query
            ->orderByRaw("CASE {$cases} ELSE 0 END DESC")
            ->orderByDesc('play_count');
    }

    private function transformStories(Collection $stories): array
    {
        return $stories->map(function (Story $story) {
            if (isset($story->episodes_sum_play_count)) {
                $story->play_count = (int) $story->episodes_sum_play_count;
            }

            return $story;
        })->values()->all();
    }

    private function buildFeaturedSection(User $user, int $limit): array
    {
        $stories = $this->applyGenderBoost($this->baseQuery($user), $user->gender)
            ->orderByDesc('play_count')
            ->limit($limit)
            ->get();

        if ($stories->count() < $limit) {
            $stories = $this->applyGenderBoost($this->baseQuery($user), $user->gender)
                ->orderByDesc('rating')
                ->limit($limit)
                ->get();
        }

        return [
            'type' => 'featured',
            'title' => 'ویژه',
            'stories' => $this->transformStories($stories),
        ];
    }

    private function buildForYouSection(User $user, int $limit): array
    {
        $sectionLimit = min($limit, 3);
        $favoriteIds = collect($user->favorite_category_ids ?? [])->map(fn ($id) => (int) $id)->filter()->all();

        if (!empty($favoriteIds)) {
            $stories = $this->applyGenderBoost(
                $this->baseQuery($user)->whereIn('category_id', $favoriteIds),
                $user->gender
            )
                ->orderByDesc('rating')
                ->limit($sectionLimit)
                ->get();
        } else {
            $stories = $this->applyGenderBoost($this->baseQuery($user), $user->gender)
                ->orderByDesc('play_count')
                ->limit($sectionLimit)
                ->get();
        }

        return [
            'type' => 'for_you',
            'title' => 'پیشنهادی برای تو',
            'stories' => $this->transformStories($stories),
        ];
    }

    private function buildByAgeSection(User $user, int $limit): array
    {
        $sectionLimit = min($limit, 3);
        $stories = $this->applyGenderBoost($this->baseQuery($user), $user->gender)
            ->orderByDesc('rating')
            ->limit($sectionLimit)
            ->get();

        return [
            'type' => 'by_age',
            'title' => 'مناسب سنت',
            'stories' => $this->transformStories($stories),
        ];
    }

    private function buildByCategorySection(User $user, int $limit): array
    {
        $favoriteIds = collect($user->favorite_category_ids ?? [])->map(fn ($id) => (int) $id)->filter()->all();

        if (empty($favoriteIds)) {
            return [
                'type' => 'by_category',
                'title' => 'دسته‌بندی‌های مورد علاقه',
                'stories' => [],
            ];
        }

        $categories = Category::whereIn('id', $favoriteIds)->where('is_active', true)->pluck('name', 'id');
        $stories = $this->applyGenderBoost(
            $this->baseQuery($user)->whereIn('category_id', $favoriteIds),
            $user->gender
        )
            ->orderByDesc('play_count')
            ->limit($limit)
            ->get();

        return [
            'type' => 'by_category',
            'title' => 'دسته‌بندی‌های مورد علاقه',
            'stories' => $this->transformStories($stories),
            'category_names' => $categories->values()->all(),
        ];
    }

    private function buildBasedOnInterestsSection(User $user, int $limit): array
    {
        $recommendations = $this->recommendationService->getForUser($user, $limit);

        return [
            'type' => 'based_on_interests',
            'title' => 'بر اساس علایقت',
            'stories' => $recommendations,
        ];
    }

    private function buildEducationalSection(User $user, int $limit): array
    {
        $educationalCategoryIds = Category::query()
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->where('name', 'like', '%آموز%')
                    ->orWhere('name', 'like', '%علم%')
                    ->orWhere('slug', 'like', '%educational%')
                    ->orWhere('slug', 'like', '%learning%');
            })
            ->pluck('id');

        $stories = $this->applyGenderBoost(
            $this->baseQuery($user)->whereIn('category_id', $educationalCategoryIds),
            $user->gender
        )
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();

        return [
            'type' => 'educational',
            'title' => 'داستان‌های آموزشی',
            'stories' => $this->transformStories($stories),
        ];
    }

    private function buildNewSection(User $user, int $limit): array
    {
        $stories = $this->applyGenderBoost($this->baseQuery($user), $user->gender)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return [
            'type' => 'new',
            'title' => 'جدیدترین‌ها',
            'stories' => $this->transformStories($stories),
        ];
    }
}

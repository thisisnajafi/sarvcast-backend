<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Story;
use App\Models\User;

class UserStoryContributionService
{
    public const ROLE_LABELS = [
        'author' => 'نویسنده',
        'narrator' => 'راوی',
        'voice_actor' => 'صداپیشه',
    ];

    /**
     * Stories where the user contributed as author, narrator, and/or character voice actor.
     *
     * @return list<array{
     *   story_id: int,
     *   title: string,
     *   status: string|null,
     *   roles: list<string>,
     *   role_labels: list<string>,
     *   characters: list<string>
     * }>
     */
    public function summarizeForUser(User $user): array
    {
        $byStory = [];

        Story::query()
            ->select(['id', 'title', 'status'])
            ->where('author_id', $user->id)
            ->orderBy('title')
            ->each(function (Story $story) use (&$byStory) {
                $this->appendRole($byStory, $story, 'author');
            });

        Story::query()
            ->select(['id', 'title', 'status'])
            ->where('narrator_id', $user->id)
            ->orderBy('title')
            ->each(function (Story $story) use (&$byStory) {
                $this->appendRole($byStory, $story, 'narrator');
            });

        Character::query()
            ->where('voice_actor_id', $user->id)
            ->with(['story:id,title,status'])
            ->orderBy('name')
            ->get()
            ->each(function (Character $character) use (&$byStory) {
                if (! $character->story) {
                    return;
                }

                $this->appendRole($byStory, $character->story, 'voice_actor');

                if ($character->name) {
                    $byStory[$character->story_id]['characters'][] = $character->name;
                }
            });

        return collect($byStory)
            ->map(function (array $entry) {
                $entry['characters'] = array_values(array_unique($entry['characters']));
                $entry['role_labels'] = array_values(array_map(
                    fn (string $role) => self::ROLE_LABELS[$role] ?? $role,
                    $entry['roles'],
                ));

                return $entry;
            })
            ->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $byStory
     */
    private function appendRole(array &$byStory, Story $story, string $role): void
    {
        $storyId = $story->id;

        if (! isset($byStory[$storyId])) {
            $byStory[$storyId] = [
                'story_id' => $storyId,
                'title' => $story->title,
                'status' => $story->status,
                'roles' => [],
                'role_labels' => [],
                'characters' => [],
            ];
        }

        if (! in_array($role, $byStory[$storyId]['roles'], true)) {
            $byStory[$storyId]['roles'][] = $role;
        }
    }
}

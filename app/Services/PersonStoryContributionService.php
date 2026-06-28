<?php

namespace App\Services;

use App\Models\Person;

class PersonStoryContributionService
{
    public const ROLE_LABELS = [
        'author' => 'نویسنده',
        'narrator' => 'راوی',
        'voice_actor' => 'صداپیشه',
        'director' => 'کارگردان',
        'producer' => 'تهیه‌کننده',
        'writer' => 'نویسنده',
    ];

    /**
     * @return list<array{
     *   story_id: int,
     *   title: string,
     *   status: string|null,
     *   roles: list<string>,
     *   role_labels: list<string>,
     *   characters: list<string>
     * }>
     */
    public function summarizeForPerson(Person $person): array
    {
        $byStory = [];

        foreach ($person->stories as $story) {
            $role = $story->pivot->role ?? 'voice_actor';
            $this->appendRole($byStory, $story->id, $story->title, $story->status, $role);
        }

        foreach ($person->episodes as $episode) {
            if (! $episode->story) {
                continue;
            }

            $role = $episode->pivot->role ?? 'voice_actor';
            $this->appendRole(
                $byStory,
                $episode->story->id,
                $episode->story->title,
                $episode->story->status,
                $role,
            );
        }

        foreach ($person->episodeVoiceActors as $assignment) {
            $episode = $assignment->episode;
            if (! $episode?->story) {
                continue;
            }

            $story = $episode->story;
            $this->appendRole($byStory, $story->id, $story->title, $story->status, 'voice_actor');

            $label = $assignment->character_name ?: $assignment->role;
            if ($label) {
                $byStory[$story->id]['characters'][] = $label;
            }
        }

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
    private function appendRole(
        array &$byStory,
        int $storyId,
        string $title,
        ?string $status,
        string $role,
    ): void {
        if (! isset($byStory[$storyId])) {
            $byStory[$storyId] = [
                'story_id' => $storyId,
                'title' => $title,
                'status' => $status,
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

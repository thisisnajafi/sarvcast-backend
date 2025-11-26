<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Episode;
use App\Models\ImageTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoryExportController extends Controller
{
    /**
     * Export selected stories, their episodes, and timelines as JSON.
     */
    public function exportJson(Request $request)
    {
        $request->validate([
            'story_ids' => 'nullable|array',
            'story_ids.*' => 'integer|exists:stories,id',
        ]);

        $query = Story::with([
            'category',
            'director',
            'writer',
            'author',
            'narrator',
            'people',
            'episodes.narrator',
            'episodes.people',
            'episodes.imageTimelines',
        ]);

        if ($request->filled('story_ids')) {
            $query->whereIn('id', $request->story_ids);
        }

        $stories = $query->get();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'stories' => [],
        ];

        foreach ($stories as $story) {
            $payload['stories'][] = [
                'story' => $story->toArray(),
                'episodes' => $story->episodes->map(function (Episode $episode) {
                    return [
                        'episode' => $episode->toArray(),
                        'image_timelines' => $episode->imageTimelines->map(function (ImageTimeline $timeline) {
                            return $timeline->toArray();
                        })->all(),
                        'people' => $episode->people->map(function ($person) {
                            return [
                                'id' => $person->id,
                                'pivot' => $person->pivot ? $person->pivot->toArray() : null,
                            ];
                        })->all(),
                    ];
                })->all(),
                'people' => $story->people->map(function ($person) {
                    return [
                        'id' => $person->id,
                        'pivot' => $person->pivot ? $person->pivot->toArray() : null,
                    ];
                })->all(),
            ];
        }

        $filename = 'stories_export_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($payload)
            ->withHeaders([
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
    }

    /**
     * Import stories, episodes, and timelines from JSON.
     *
     * This assumes that related lookup data (categories, people, etc.) already exist
     * and uses their IDs as provided in the JSON.
     */
    public function importJson(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,txt',
        ]);

        $file = $request->file('file');
        $json = file_get_contents($file->getRealPath());

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['stories']) || !is_array($data['stories'])) {
            return redirect()->back()->with('error', 'ساختار فایل JSON نامعتبر است.');
        }

        try {
            DB::beginTransaction();

            foreach ($data['stories'] as $storyBundle) {
                if (!isset($storyBundle['story'])) {
                    continue;
                }

                $storyData = $storyBundle['story'];

                // Remove IDs to avoid collisions; create as new stories.
                unset($storyData['id'], $storyData['created_at'], $storyData['updated_at']);

                $story = Story::create($storyData);

                // Attach story people if provided
                if (!empty($storyBundle['people']) && is_array($storyBundle['people'])) {
                    $peopleData = [];
                    foreach ($storyBundle['people'] as $personItem) {
                        if (!isset($personItem['id'])) {
                            continue;
                        }
                        $pivot = $personItem['pivot'] ?? [];
                        $peopleData[$personItem['id']] = [
                            'role' => $pivot['role'] ?? 'voice_actor',
                        ];
                    }
                    if (!empty($peopleData)) {
                        $story->people()->attach($peopleData);
                    }
                }

                // Import episodes and timelines
                if (!empty($storyBundle['episodes']) && is_array($storyBundle['episodes'])) {
                    foreach ($storyBundle['episodes'] as $episodeBundle) {
                        if (empty($episodeBundle['episode']) || !is_array($episodeBundle['episode'])) {
                            continue;
                        }

                        $episodeData = $episodeBundle['episode'];
                        unset($episodeData['id'], $episodeData['story_id'], $episodeData['created_at'], $episodeData['updated_at']);
                        $episodeData['story_id'] = $story->id;

                        $episode = Episode::create($episodeData);

                        // Attach episode people if provided
                        if (!empty($episodeBundle['people']) && is_array($episodeBundle['people'])) {
                            $episodePeople = [];
                            foreach ($episodeBundle['people'] as $personItem) {
                                if (!isset($personItem['id'])) {
                                    continue;
                                }
                                $pivot = $personItem['pivot'] ?? [];
                                $episodePeople[$personItem['id']] = [
                                    'role' => $pivot['role'] ?? 'voice_actor',
                                ];
                            }
                            if (!empty($episodePeople)) {
                                $episode->people()->attach($episodePeople);
                            }
                        }

                        // Create image timelines if provided
                        if (!empty($episodeBundle['image_timelines']) && is_array($episodeBundle['image_timelines'])) {
                            foreach ($episodeBundle['image_timelines'] as $timelineData) {
                                if (!is_array($timelineData)) {
                                    continue;
                                }

                                unset($timelineData['id'], $timelineData['story_id'], $timelineData['episode_id'], $timelineData['created_at'], $timelineData['updated_at']);
                                $timelineData['story_id'] = $story->id;
                                $timelineData['episode_id'] = $episode->id;

                                ImageTimeline::create($timelineData);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'داستان‌ها و اپیزودها با موفقیت از فایل JSON وارد شدند.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Story JSON import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'خطا در وارد کردن داده‌ها: ' . $e->getMessage());
        }
    }
}



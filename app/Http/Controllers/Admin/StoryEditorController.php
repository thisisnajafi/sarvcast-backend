<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoryEpisodeRequest;
use App\Http\Support\AdminApiResponse;
use App\Services\StoryEditorRepository;
use Illuminate\Support\Facades\Log;

class StoryEditorController extends Controller
{
    public function __construct(
        private readonly StoryEditorRepository $repository,
    ) {}

    public function index()
    {
        try {
            $stories = $this->repository->listStories();

            return AdminApiResponse::success($stories);
        } catch (\Throwable $e) {
            Log::error('Story editor list stories failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست داستان‌ها.',
            ], 500);
        }
    }

    public function episodes(string $storyId)
    {
        $storyDir = $this->repository->findStoryDirectory($storyId);
        if ($storyDir === null) {
            return response()->json([
                'success' => false,
                'message' => 'داستان یافت نشد.',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        return AdminApiResponse::success($this->repository->listEpisodes($storyId));
    }

    public function show(string $storyId, string $episodeId)
    {
        $data = $this->repository->getEpisode($storyId, $episodeId);
        if ($data === null) {
            return response()->json([
                'success' => false,
                'message' => 'قسمت یافت نشد.',
                'error' => 'NOT_FOUND',
            ], 404);
        }

        return AdminApiResponse::success($data);
    }

    public function update(UpdateStoryEpisodeRequest $request, string $storyId, string $episodeId)
    {
        try {
            $payload = $request->validated();
            $result = $this->repository->saveEpisode($storyId, $episodeId, $payload);

            if ($result === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'قسمت یافت نشد.',
                    'error' => 'NOT_FOUND',
                ], 404);
            }

            return AdminApiResponse::success($result, 'قسمت با موفقیت ذخیره شد. نسخه پشتیبان ایجاد شد.');
        } catch (\Throwable $e) {
            Log::error('Story editor save episode failed', [
                'story_id' => $storyId,
                'episode_id' => $episodeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ذخیره فایل قسمت.',
            ], 500);
        }
    }
}

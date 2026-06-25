<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoryEpisodeRequest;
use App\Http\Support\AdminApiResponse;
use App\Services\ActivityLogService;
use App\Services\StoryEditorRepository;
use App\Services\StoryProductionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoryEditorController extends Controller
{
    public function __construct(
        private readonly StoryEditorRepository $repository,
        private readonly StoryProductionImportService $importService,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index()
    {
        try {
            $stories = $this->repository->listStories();

            return AdminApiResponse::success($stories);
        } catch (\RuntimeException $e) {
            Log::error('Story editor list stories failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'STORIES_PATH_NOT_FOUND',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Story editor list stories failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست داستان‌ها.',
            ], 500);
        }
    }

    public function storeStory(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'db_story_id' => ['nullable', 'integer', 'exists:stories,id'],
        ]);

        try {
            $scaffold = $this->repository->createStoryScaffold(
                $validated['title'],
                $validated['db_story_id'] ?? null,
            );

            return AdminApiResponse::success($scaffold, 'پوشه داستان در storage ایجاد شد.', 201);
        } catch (\Throwable $e) {
            Log::error('Story editor create story scaffold failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeEpisode(Request $request, string $storyId)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'episode_number' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $scaffold = $this->repository->createEpisodeScaffold(
                $storyId,
                (int) $validated['episode_number'],
                $validated['title'],
            );

            return AdminApiResponse::success($scaffold, 'پوشه قسمت در storage ایجاد شد.', 201);
        } catch (\Throwable $e) {
            Log::error('Story editor create episode scaffold failed', [
                'story_id' => $storyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function resolveSlug(Request $request)
    {
        $validated = $request->validate([
            'story_id' => ['required', 'integer', 'exists:stories,id'],
        ]);

        $slug = $this->importService->resolveStorySlugByDbStoryId((int) $validated['story_id']);

        return AdminApiResponse::success([
            'story_slug' => $slug,
            'found' => $slug !== null,
        ]);
    }

    public function package(string $storyId)
    {
        try {
            return AdminApiResponse::success($this->importService->getPackageOverview($storyId));
        } catch (\Throwable $e) {
            Log::error('Story editor package overview failed', ['story_id' => $storyId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], str_contains($e->getMessage(), 'یافت نشد') ? 404 : 500);
        }
    }

    public function assets(Request $request, string $storyId)
    {
        $request->validate([
            'episode_slug' => ['nullable', 'string', 'max:191'],
            'asset_type' => ['nullable', Rule::in(['character', 'object', 'setting', 'scene', 'cover'])],
        ]);

        try {
            $assets = $this->importService->listAssets(
                $storyId,
                $request->query('episode_slug'),
                $request->query('asset_type'),
            );

            return AdminApiResponse::success($assets);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function import(Request $request, string $storyId, ?string $episodeId = null)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'db_story_id' => ['nullable', 'integer', 'exists:stories,id'],
            'db_episode_id' => ['nullable', 'integer', 'exists:episodes,id'],
        ]);

        try {
            $result = $this->importService->importStoryFile(
                $storyId,
                $request->file('file'),
                $episodeId,
                $request->filled('db_story_id') ? $request->integer('db_story_id') : null,
                $request->filled('db_episode_id') ? $request->integer('db_episode_id') : null,
            );

            $this->activityLog->recordStoryEditorImport(
                $request,
                $storyId,
                $episodeId,
                $request->file('file')->getClientOriginalName(),
            );

            return AdminApiResponse::success($result, 'فایل با موفقیت import شد.');
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Story editor import failed', [
                'story_id' => $storyId,
                'episode_id' => $episodeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در import فایل: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function uploadAssetImage(Request $request, string $storyId, string $assetType, string $assetKey)
    {
        $request->validate([
            'image' => ['required_without:image_url', 'image', 'max:10240'],
            'image_url' => ['required_without:image', 'string', 'url', 'max:500'],
            'episode_slug' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            if ($request->hasFile('image')) {
                $result = $this->importService->uploadAssetImage(
                    $storyId,
                    $assetType,
                    $assetKey,
                    $request->file('image'),
                    $request->input('episode_slug'),
                );
            } else {
                $result = $this->importService->assignAssetImageUrl(
                    $storyId,
                    $assetType,
                    $assetKey,
                    (string) $request->input('image_url'),
                    $request->input('episode_slug'),
                );
            }

            return AdminApiResponse::success($result, 'تصویر با موفقیت ثبت شد.');
        } catch (\Throwable $e) {
            Log::error('Story editor asset upload failed', [
                'story_id' => $storyId,
                'asset_type' => $assetType,
                'asset_key' => $assetKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], str_contains($e->getMessage(), 'یافت نشد') ? 404 : 500);
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
            $before = $this->repository->getEpisode($storyId, $episodeId);
            $payload = $request->validated();
            $result = $this->repository->saveEpisode($storyId, $episodeId, $payload);

            if ($result === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'قسمت یافت نشد.',
                    'error' => 'NOT_FOUND',
                ], 404);
            }

            if ($before !== null) {
                $this->activityLog->recordStoryEditorEpisodeSaved(
                    $request,
                    $storyId,
                    $episodeId,
                    $before['episode'] ?? [],
                    $result['episode'] ?? [],
                    $result['backup_path'] ?? null,
                );
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

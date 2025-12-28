<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\PlayHistory;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EpisodeController extends Controller
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Standardized success response
     */
    private function successResponse($data = null, $message = null, $meta = null)
    {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * Standardized error response
     */
    private function errorResponse($message, $errorCode = null, $statusCode = 400, $data = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if ($data) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
    /**
     * Get paginated list of episodes
     */
    public function index(Request $request)
    {
        $query = Episode::with(['story', 'narrator', 'people', 'imageTimelines.voiceActor.person'])
            ->published();

        // Apply filters
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'episode_number');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSortFields = ['episode_number', 'title', 'duration', 'play_count', 'rating', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $episodes = $query->paginate($request->get('per_page', 20));

        return $this->successResponse(
            $episodes->items(),
            'Episodes retrieved successfully',
            [
                'pagination' => [
                    'current_page' => $episodes->currentPage(),
                    'last_page' => $episodes->lastPage(),
                    'per_page' => $episodes->perPage(),
                    'total' => $episodes->total()
                ]
            ]
        );
    }

    /**
     * Get episode details
     */
    public function show(Request $request, Episode $episode)
    {
        $includeTimeline = $request->get('include_timeline', false);

        $episode->load(['story', 'narrator', 'people', 'imageTimelines.voiceActor.person']);

        // Check access control
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse(
                'احراز هویت الزامی است',
                'AUTHENTICATION_REQUIRED',
                401,
                [
                    'access_info' => [
                        'has_access' => false,
                        'reason' => 'authentication_required',
                        'message' => 'احراز هویت الزامی است'
                    ],
                    'upgrade_required' => false
                ]
            );
        }

        $accessInfo = $this->accessControlService->canAccessEpisode($user->id, $episode->id);

        if (!$accessInfo['has_access']) {
            return response()->json([
                'success' => false,
                'message' => $accessInfo['message'],
                'error_code' => strtoupper($accessInfo['reason']),
                'data' => [
                    'access_info' => $accessInfo,
                    'upgrade_required' => $accessInfo['reason'] === 'premium_required'
                ]
            ], 403);
        }

        // Check if the story is in user's favorites
        $isStoryFavorited = \App\Models\Favorite::isFavorited($user->id, $episode->story_id);

        $responseData = [
            'episode' => $episode,
            'access_info' => $accessInfo,
            'is_story_favorited' => $isStoryFavorited
        ];

        // Add timeline data if episode uses image timeline
        if ($episode->use_image_timeline) {
            $responseData['image_timeline'] = $episode->imageTimelines->map(function($timeline) {
                return $timeline->toApiResponse();
            });
        }

        return response()->json([
            'success' => true,
            'data' => $responseData
        ]);
    }

    /**
     * Record episode play
     */
    public function play(Request $request, Episode $episode)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Check access control
        $accessInfo = $this->accessControlService->canAccessEpisode($user->id, $episode->id);

        if (!$accessInfo['has_access']) {
            return response()->json([
                'success' => false,
                'message' => $accessInfo['message'],
                'error_code' => strtoupper($accessInfo['reason']),
                'data' => [
                    'access_info' => $accessInfo,
                    'upgrade_required' => $accessInfo['reason'] === 'premium_required'
                ]
            ], 403);
        }

        // Record play history
        PlayHistory::create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'story_id' => $episode->story_id,
            'played_at' => now(),
            'duration_played' => 0, // Default to 0, can be updated later
            'total_duration' => $episode->duration,
            'completed' => false
        ]);

        // Update episode play count
        $episode->increment('play_count');

        return response()->json([
            'success' => true,
            'message' => 'Episode play recorded',
            'data' => [
                'episode' => $episode->fresh()
            ]
        ]);
    }

    /**
     * Bookmark episode
     */
    public function bookmark(Episode $episode)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Check if already bookmarked
        $existingBookmark = $user->favorites()->where('episode_id', $episode->id)->first();

        if ($existingBookmark) {
            return response()->json([
                'success' => false,
                'message' => 'Episode is already bookmarked'
            ], 400);
        }

        // Create bookmark
        $user->favorites()->create([
            'episode_id' => $episode->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Episode bookmarked successfully'
        ]);
    }

    /**
     * Remove episode bookmark
     */
    public function removeBookmark(Episode $episode)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        $user->favorites()->where('episode_id', $episode->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Episode bookmark removed successfully'
        ]);
    }

    /**
     * Upload script file for an episode (admin & super admin)
     * 
     * @param Request $request
     * @param Episode $episode
     * @return JsonResponse
     */
    public function uploadScript(Request $request, Episode $episode)
    {
        $request->validate([
            'script' => [
                'required',
                'file',
                'mimes:md,txt',
                'max:5120', // 5MB max
            ],
        ], [
            'script.required' => 'فایل اسکریپت الزامی است',
            'script.file' => 'فایل باید یک فایل معتبر باشد',
            'script.mimes' => 'فرمت فایل باید .md یا .txt باشد',
            'script.max' => 'حجم فایل نمی‌تواند بیش از 5 مگابایت باشد',
        ]);

        try {
            $file = $request->file('script');
            $filename = time() . '_' . $episode->id . '_episode_' . $episode->episode_number . '.' . $file->getClientOriginalExtension();
            $directory = 'episodes/scripts';
            
            $path = $file->storeAs($directory, $filename, 'public');
            $url = Storage::url($path);

            $episode->update(['script_file_url' => $url]);

            return response()->json([
                'success' => true,
                'message' => 'فایل اسکریپت با موفقیت آپلود شد.',
                'data' => [
                    'script_file_url' => $url,
                    'filename' => $filename,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Episode script upload failed', [
                'error' => $e->getMessage(),
                'episode_id' => $episode->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود فایل اسکریپت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get episode script content
     *
     * @param Episode $episode
     * @return JsonResponse
     */
    public function getScript(Episode $episode)
    {
        if (!$episode->script_file_url) {
            return response()->json([
                'success' => false,
                'message' => 'فایل اسکریپت برای این قسمت موجود نیست.'
            ], 404);
        }

        try {
            // Extract path from URL
            $path = str_replace('/storage/', '', parse_url($episode->script_file_url, PHP_URL_PATH));
            
            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل اسکریپت یافت نشد.'
                ], 404);
            }

            $content = Storage::disk('public')->get($path);

            return response()->json([
                'success' => true,
                'message' => 'محتوای اسکریپت با موفقیت دریافت شد.',
                'data' => [
                    'script_content' => $content,
                    'script_file_url' => $episode->script_file_url,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Episode script retrieval failed', [
                'error' => $e->getMessage(),
                'episode_id' => $episode->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فایل اسکریپت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new episode (admin & super admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,aac,ogg|max:102400', // 100MB max, optional
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'is_premium' => 'nullable|boolean',
            'status' => 'required|in:draft,published,archived',
        ], [
            'story_id.required' => 'شناسه داستان الزامی است',
            'story_id.exists' => 'داستان انتخاب شده معتبر نیست',
            'title.required' => 'عنوان قسمت الزامی است',
            'title.max' => 'عنوان نمی‌تواند بیشتر از 200 کاراکتر باشد',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 2000 کاراکتر باشد',
            'episode_number.required' => 'شماره قسمت الزامی است',
            'episode_number.min' => 'شماره قسمت باید حداقل 1 باشد',
            'duration.required' => 'مدت زمان الزامی است',
            'duration.min' => 'مدت زمان باید حداقل 1 ثانیه باشد',
            'audio_file.mimes' => 'فرمت فایل صوتی باید mp3، wav، m4a، aac یا ogg باشد',
            'audio_file.max' => 'حجم فایل صوتی نمی‌تواند بیشتر از 100 مگابایت باشد',
            'cover_image.image' => 'فایل باید یک تصویر معتبر باشد',
            'cover_image.mimes' => 'فرمت تصویر باید jpeg، png، jpg یا webp باشد',
            'cover_image.max' => 'حجم تصویر نمی‌تواند بیشتر از 5 مگابایت باشد',
            'status.required' => 'وضعیت الزامی است',
            'status.in' => 'وضعیت نامعتبر است',
        ]);

        try {
            $validated = $request->only([
                'story_id', 'title', 'description', 'episode_number', 'duration',
                'is_premium', 'status'
            ]);

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                $audioFile = $request->file('audio_file');
                $audioName = time() . '_' . Str::slug($request->title) . '_' . uniqid() . '.' . $audioFile->getClientOriginalExtension();
                
                // Ensure directory exists
                $audioDir = public_path('audio/episodes');
                if (!file_exists($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }
                
                $audioFile->move($audioDir, $audioName);
                $validated['audio_url'] = 'audio/episodes/' . $audioName;
            }
            
            // Handle cover image upload
            if ($request->hasFile('cover_image')) {
                $coverImage = $request->file('cover_image');
                $coverImageName = time() . '_cover_' . Str::slug($request->title) . '_' . uniqid() . '.' . $coverImage->getClientOriginalExtension();
                
                // Ensure directory exists
                $imageDir = public_path('images/episodes');
                if (!file_exists($imageDir)) {
                    mkdir($imageDir, 0755, true);
                }
                
                $coverImage->move($imageDir, $coverImageName);
                $validated['cover_image_url'] = 'episodes/' . $coverImageName;
            }

            $episode = Episode::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'قسمت با موفقیت ایجاد شد.',
                'data' => $episode->load(['story'])
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Episode creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد قسمت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an episode (admin & super admin)
     */
    public function update(Request $request, Episode $episode)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'sometimes|required|integer|min:1',
            'duration' => 'sometimes|required|integer|min:1',
            'audio_file' => 'sometimes|file|mimes:mp3,wav,m4a,aac,ogg|max:102400',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_premium' => 'nullable|boolean',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);

        try {
            $validated = $request->only([
                'title', 'description', 'episode_number', 'duration',
                'is_premium', 'status'
            ]);

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file if exists
                if ($episode->audio_url) {
                    $oldPath = public_path($episode->audio_url);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $audioFile = $request->file('audio_file');
                $audioName = time() . '_' . Str::slug($request->title ?? $episode->title) . '_' . uniqid() . '.' . $audioFile->getClientOriginalExtension();
                
                $audioDir = public_path('audio/episodes');
                if (!file_exists($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }
                
                $audioFile->move($audioDir, $audioName);
                $validated['audio_url'] = 'audio/episodes/' . $audioName;
            }
            
            // Handle cover image upload
            if ($request->hasFile('cover_image')) {
                // Delete old cover image if exists
                if ($episode->cover_image_url) {
                    $oldPath = public_path('images/' . $episode->cover_image_url);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $coverImage = $request->file('cover_image');
                $coverImageName = time() . '_cover_' . Str::slug($request->title ?? $episode->title) . '_' . uniqid() . '.' . $coverImage->getClientOriginalExtension();
                
                $imageDir = public_path('images/episodes');
                if (!file_exists($imageDir)) {
                    mkdir($imageDir, 0755, true);
                }
                
                $coverImage->move($imageDir, $coverImageName);
                $validated['cover_image_url'] = 'episodes/' . $coverImageName;
            }

            $episode->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'قسمت با موفقیت به‌روزرسانی شد.',
                'data' => $episode->load(['story'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Episode update failed', [
                'error' => $e->getMessage(),
                'episode_id' => $episode->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی قسمت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an episode (admin & super admin)
     */
    public function destroy(Episode $episode)
    {
        try {
            // Delete associated files
            if ($episode->audio_url) {
                $audioPath = public_path($episode->audio_url);
                if (file_exists($audioPath)) {
                    unlink($audioPath);
                }
            }

            if ($episode->cover_image_url) {
                $imagePath = public_path('images/' . $episode->cover_image_url);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            if ($episode->script_file_url) {
                $scriptPath = str_replace('/storage/', '', parse_url($episode->script_file_url, PHP_URL_PATH));
                if (Storage::disk('public')->exists($scriptPath)) {
                    Storage::disk('public')->delete($scriptPath);
                }
            }

            $episode->delete();

            return response()->json([
                'success' => true,
                'message' => 'قسمت با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Episode deletion failed', [
                'error' => $e->getMessage(),
                'episode_id' => $episode->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف قسمت: ' . $e->getMessage()
            ], 500);
        }
    }
}

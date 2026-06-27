<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Support\AdminApiResponse;
use App\Models\Episode;
use App\Models\Story;
use App\Models\Person;
use App\Models\User;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use App\Services\AudioProcessingService;
use App\Services\ImageProcessingService;
use App\Services\MediaLibraryService;
use App\Services\StoryEpisodeStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EpisodeController extends BaseController
{
    protected $notificationService;
    protected $pushNotificationService;
    protected $audioProcessingService;
    protected $imageProcessingService;

    public function __construct(
        InAppNotificationService $notificationService,
        NotificationService $pushNotificationService,
        AudioProcessingService $audioProcessingService,
        ImageProcessingService $imageProcessingService
    ) {
        $this->notificationService = $notificationService;
        $this->pushNotificationService = $pushNotificationService;
        $this->audioProcessingService = $audioProcessingService;
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Generate unique filename with random string and datetime
     */
    private function generateUniqueFilename($file, $prefix = '')
    {
        $extension = $file->getClientOriginalExtension();
        $randomString = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 32);
        $datetime = now()->format('Y-m-d_H-i-s');
        $prefix = $prefix ? $prefix . '_' : '';

        return $prefix . $randomString . '-' . $datetime . '.' . $extension;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Episode::with(['story', 'narrator', 'people']);

        // Apply filters
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('narrator_id')) {
            $query->where('narrator_id', $request->narrator_id);
        }

        if ($request->filled('episode_number')) {
            $query->where('episode_number', $request->episode_number);
        }

        if ($request->filled('min_duration')) {
            $query->where('duration', '>=', $request->min_duration);
        }

        if ($request->filled('max_duration')) {
            $query->where('duration', '<=', $request->max_duration);
        }

        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        if ($request->filled('min_play_count')) {
            $query->where('play_count', '>=', $request->min_play_count);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('release_date_from')) {
            $query->whereDate('release_date', '>=', $request->release_date_from);
        }

        if ($request->filled('release_date_to')) {
            $query->whereDate('release_date', '<=', $request->release_date_to);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('story', function($storyQuery) use ($request) {
                      $storyQuery->where('title', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        switch ($sort) {
            case 'title':
                $query->orderBy('title', $direction);
                break;
            case 'episode_number':
                $query->orderBy('episode_number', $direction);
                break;
            case 'status':
                $query->orderBy('status', $direction);
                break;
            case 'rating':
                $query->orderBy('rating', $direction);
                break;
            case 'play_count':
                $query->orderBy('play_count', $direction);
                break;
            case 'duration':
                $query->orderBy('duration', $direction);
                break;
            case 'release_date':
                $query->orderBy('release_date', $direction);
                break;
            case 'story_title':
                $query->join('stories', 'episodes.story_id', '=', 'stories.id')
                      ->orderBy('stories.title', $direction)
                      ->select('episodes.*');
                break;
            default:
                $query->orderBy('created_at', $direction);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $episodes = $query->paginate($perPage);

        // Get filter options
        $stories = Story::where('status', 'published')->get();
        $narrators = Person::whereJsonContains('roles', 'narrator')->get();

        // Get statistics
        $stats = [
            'total' => Episode::count(),
            'published' => Episode::where('status', 'published')->count(),
            'draft' => Episode::where('status', 'draft')->count(),
            'archived' => Episode::where('status', 'archived')->count(),
            'premium' => Episode::where('is_premium', true)->count(),
            'free' => Episode::where('is_premium', false)->count(),
            'total_duration' => Episode::sum('duration'),
            'avg_rating' => Episode::avg('rating'),
            'total_plays' => Episode::sum('play_count'),
            'avg_duration' => Episode::avg('duration')
        ];

        return view('admin.episodes.index', compact('episodes', 'stories', 'narrators', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stories = Story::with(['category', 'narrator'])->orderBy('title', 'asc')->get();
        $people = Person::orderBy('name', 'asc')->get();

        // Get narrators (Person model) for episodes - episodes use Person, not User
        $narrators = Person::whereJsonContains('roles', 'narrator')
            ->orderBy('name', 'asc')
            ->get();

        // Create a map of story_id => narrator_name for auto-selection
        $storyNarrators = [];
        // Create a map of story_id => is_premium for premium status handling
        $storyPremiumStatus = [];
        foreach ($stories as $story) {
            if ($story->narrator) {
                // Try to find matching Person by name
                $narratorName = $story->narrator->first_name . ' ' . $story->narrator->last_name;
                $matchingPerson = $narrators->first(function($person) use ($narratorName) {
                    return $person->name === $narratorName ||
                           stripos($person->name, $narratorName) !== false ||
                           stripos($narratorName, $person->name) !== false;
                });
                if ($matchingPerson) {
                    $storyNarrators[$story->id] = $matchingPerson->id;
                }
            }
            $storyPremiumStatus[$story->id] = $story->is_premium;
        }

        return view('admin.episodes.create', compact('stories', 'people', 'narrators', 'storyNarrators', 'storyPremiumStatus'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'audio_file' => 'required|file|mimes:mp3,wav,m4a,aac,ogg|max:102400', // 100MB max
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'narrator_id' => 'nullable|exists:people,id',
            'script_file' => 'nullable|file|mimes:md,txt,doc,docx|max:10240', // 10MB max
            'is_premium' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'release_date' => 'nullable|date',
            'people' => 'nullable|array',
            'people.*' => 'exists:people,id',
            'process_audio' => 'boolean',
            'audio_quality' => 'nullable|in:low,medium,high',
            'resize_image' => 'boolean',
            'image_width' => 'nullable|integer|min:100|max:2000',
            'image_height' => 'nullable|integer|min:100|max:2000',
        ], [
            'story_id.required' => 'انتخاب داستان الزامی است',
            'story_id.exists' => 'داستان انتخاب شده یافت نشد',
            'title.required' => 'عنوان اپیزود الزامی است',
            'title.max' => 'عنوان اپیزود نمی‌تواند بیشتر از 200 کاراکتر باشد',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 2000 کاراکتر باشد',
            'episode_number.required' => 'شماره اپیزود الزامی است',
            'episode_number.min' => 'شماره اپیزود باید حداقل 1 باشد',
            'duration.required' => 'مدت زمان الزامی است',
            'duration.min' => 'مدت زمان باید حداقل 1 ثانیه باشد',
            'audio_file.required' => 'فایل صوتی الزامی است',
            'audio_file.mimes' => 'فرمت فایل صوتی باید mp3، wav، m4a، aac یا ogg باشد',
            'audio_file.max' => 'حجم فایل صوتی نمی‌تواند بیشتر از 100 مگابایت باشد',
            'cover_image.image' => 'فایل تصویر باید یک تصویر معتبر باشد',
            'cover_image.mimes' => 'فرمت تصویر باید jpeg، png، jpg یا webp باشد',
            'cover_image.max' => 'حجم تصویر نمی‌تواند بیشتر از 5 مگابایت باشد',
            'narrator_id.exists' => 'راوی انتخاب شده یافت نشد',
            'status.required' => 'وضعیت الزامی است',
            'status.in' => 'وضعیت نامعتبر است',
            'release_date.date' => 'تاریخ انتشار نامعتبر است',
            'people.array' => 'افراد باید آرایه باشند',
            'people.*.exists' => 'یکی از افراد یافت نشد'
        ]);

        try {
            DB::beginTransaction();

            $data = $request->except(['audio_file', 'cover_image', 'people', 'process_audio', 'audio_quality', 'resize_image', 'image_width', 'image_height']);

            // Handle audio file upload and processing
            if ($request->hasFile('audio_file')) {
                $audioFile = $request->file('audio_file');

                // Ensure directory exists in public/audio/episodes
                $audioDir = public_path('audio/episodes');
                if (!file_exists($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }

                // Generate unique filename to avoid conflicts
                $filename = $this->generateUniqueFilename($audioFile, 'audio');

                // Save to public/audio/episodes directory
                $audioPath = $audioFile->move($audioDir, $filename);
                // Store the path relative to public directory
                $data['audio_url'] = 'audio/episodes/' . $filename;

                // Process audio if requested
                if ($request->boolean('process_audio')) {
                    try {
                        $audioQuality = $request->input('audio_quality', 'medium');
                        $processedAudio = $this->audioProcessingService->processAudio(
                            public_path($data['audio_url']),
                            [
                                'quality' => $audioQuality,
                                'normalize' => true,
                                'extract_metadata' => true
                            ]
                        );

                        if ($processedAudio['success']) {
                            $data['duration'] = $processedAudio['data']['duration'] ?? $data['duration'];
                            $data['audio_metadata'] = json_encode($processedAudio['data']['metadata'] ?? []);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Audio processing failed', [
                            'episode_title' => $data['title'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Handle script file upload
            if ($request->hasFile('script_file')) {
                $scriptFile = $request->file('script_file');
                $scriptDir = public_path('scripts/episodes');

                // Ensure directory exists
                if (!file_exists($scriptDir)) {
                    mkdir($scriptDir, 0755, true);
                }

                $scriptFileName = time() . '_' . $scriptFile->getClientOriginalName();
                $scriptFile->move($scriptDir, $scriptFileName);
                // Store only the relative path
                $data['script_file_url'] = 'scripts/episodes/' . $scriptFileName;
            }

            // Handle cover image upload and processing
            if ($request->hasFile('cover_image')) {
                $coverImage = $request->file('cover_image');
                $imageName = $this->generateUniqueFilename($coverImage, 'cover');
                $coverImage->move(public_path('images/episodes'), $imageName);
                // Store only the relative path
                $data['cover_image_url'] = 'episodes/' . $imageName;

                // Process image if requested
                if ($request->boolean('resize_image')) {
                    try {
                        $imageWidth = $request->input('image_width', 800);
                        $imageHeight = $request->input('image_height', 600);

                        $processedImage = $this->imageProcessingService->processImage(
                            storage_path('app/public/' . str_replace('/storage/', '', $data['cover_image_url'])),
                            [
                                'resize' => ['width' => $imageWidth, 'height' => $imageHeight],
                                'optimize' => true,
                                'quality' => 85
                            ]
                        );

                        if ($processedImage['success']) {
                            // Update with processed image path if different
                            $data['cover_image_url'] = $processedImage['data']['url'] ?? $data['cover_image_url'];
                        }
                    } catch (\Exception $e) {
                        Log::warning('Image processing failed', [
                            'episode_title' => $data['title'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Auto-set premium status based on story premium status
            $story = Story::with('narrator')->find($request->story_id);
            if ($story && $story->is_premium) {
                // If story is premium, all episodes must be premium
                $data['is_premium'] = true;
            } else {
                // If story is free, use the value from request (can be premium or free)
                $data['is_premium'] = $request->boolean('is_premium', false);
            }

            // Auto-set narrator from story narrator (if story has a narrator)
            // All episodes of a story should have the same narrator as the story
            $personNarrator = $story ? $story->getMatchingPersonNarrator() : null;
            if ($personNarrator) {
                $data['narrator_id'] = $personNarrator->id;
            }

            $episode = Episode::create($data);

            // Update story statistics after creating episode
            if ($story) {
                $story->updateStatistics();
            }

            // Attach people if provided
            if ($request->filled('people')) {
                $episode->people()->attach($request->people);
            }

            // Send notification if published - only to users who favorited this story
            if ($episode->status === 'published') {
                $story = $episode->story;
                $favoritedUserIds = \App\Models\Favorite::where('story_id', $story->id)
                    ->pluck('user_id')
                    ->toArray();

                if (!empty($favoritedUserIds)) {
                    $this->notificationService->sendToMultipleUsers(
                        $favoritedUserIds,
                        'content',
                        'قسمت جدید منتشر شد',
                        "قسمت جدید \"{$episode->title}\" از داستان \"{$story->title}\" منتشر شد.",
                        [
                            'is_important' => true,
                            'action_type' => 'button',
                            'action_text' => 'شنیدن قسمت',
                            'action_url' => '/episodes/latest',
                            'data' => [
                                'story_title' => $story->title,
                                'episode_title' => $episode->title,
                                'story_id' => $story->id,
                                'episode_id' => $episode->id
                            ]
                        ]
                    );
                }
            }

            DB::commit();

            return redirect()->route('admin.episodes.index')
                ->with('success', 'اپیزود با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create episode', [
                'title' => $request->input('title'),
                'story_id' => $request->input('story_id'),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'خطا در ایجاد اپیزود: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Episode $episode)
    {
        $episode->load(['story', 'narrator', 'people', 'imageTimelines', 'voiceActors.person', 'playHistories.user']);

        return view('admin.episodes.show', compact('episode'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Episode $episode)
    {
        $stories = Story::with('category')->orderBy('title', 'asc')->get();
        $narrators = Person::whereJsonContains('roles', 'narrator')->get();
        $people = Person::orderBy('name', 'asc')->get();
        $episode->load(['imageTimelines']);

        // Create a map of story_id => is_premium for premium status handling
        $storyPremiumStatus = [];
        foreach ($stories as $story) {
            $storyPremiumStatus[$story->id] = $story->is_premium;
        }

        return view('admin.episodes.edit', compact('episode', 'stories', 'narrators', 'people', 'storyPremiumStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Episode $episode)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('episodes')->where(function ($query) use ($request) {
                    return $query->where('story_id', $request->story_id);
                })->ignore($episode->id)
            ],
            'duration' => 'required|integer|min:1',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a|max:102400',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'narrator_id' => 'nullable|exists:people,id',
            'is_premium' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'release_date' => 'nullable|date',
            'people' => 'nullable|array',
            'people.*' => 'exists:people,id',
            'process_audio' => 'boolean',
            'audio_quality' => 'nullable|in:low,medium,high',
            'resize_image' => 'boolean',
            'image_width' => 'nullable|integer|min:100|max:2000',
            'image_height' => 'nullable|integer|min:100|max:2000',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->except(['audio_file', 'cover_image', 'people', 'process_audio', 'audio_quality', 'resize_image', 'image_width', 'image_height']);

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file
                if ($episode->audio_url && file_exists(public_path($episode->audio_url))) {
                    try {
                        unlink(public_path($episode->audio_url));
                        Log::info('Old audio file deleted successfully', ['file' => $episode->audio_url]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old audio file', [
                            'file' => $episode->audio_url,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with the update even if old file deletion fails
                    }
                }

                $audioFile = $request->file('audio_file');

                // Ensure directory exists in public/audio/episodes
                $audioDir = public_path('audio/episodes');
                if (!file_exists($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }

                // Generate unique filename to avoid conflicts
                $filename = $this->generateUniqueFilename($audioFile, 'audio');

                // Save to public/audio/episodes directory
                $audioPath = $audioFile->move($audioDir, $filename);
                // Store the path relative to public directory
                $data['audio_url'] = 'audio/episodes/' . $filename;

                // Process audio if requested
                if ($request->boolean('process_audio')) {
                    try {
                        $audioQuality = $request->input('audio_quality', 'medium');
                        $processedAudio = $this->audioProcessingService->processAudio(
                            public_path($data['audio_url']),
                            [
                                'quality' => $audioQuality,
                                'normalize' => true,
                                'extract_metadata' => true
                            ]
                        );

                        if ($processedAudio['success']) {
                            $data['duration'] = $processedAudio['data']['duration'] ?? $data['duration'];
                            $data['audio_metadata'] = json_encode($processedAudio['data']['metadata'] ?? []);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Audio processing failed', [
                            'episode_title' => $data['title'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Handle cover image upload
            if ($request->hasFile('cover_image')) {
                // Delete old cover image
                if ($episode->cover_image_url && file_exists(public_path('images/' . $episode->cover_image_url))) {
                    try {
                        unlink(public_path('images/' . $episode->cover_image_url));
                        Log::info('Old cover image deleted successfully', ['file' => $episode->cover_image_url]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old cover image', [
                            'file' => $episode->cover_image_url,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with the update even if old file deletion fails
                    }
                }

                $coverImage = $request->file('cover_image');
                $imageName = $this->generateUniqueFilename($coverImage, 'cover');
                $coverImage->move(public_path('images/episodes'), $imageName);
                // Store only the relative path
                $data['cover_image_url'] = 'episodes/' . $imageName;

                // Process image if requested
                if ($request->boolean('resize_image')) {
                    try {
                        $imageWidth = $request->input('image_width', 800);
                        $imageHeight = $request->input('image_height', 600);

                        $processedImage = $this->imageProcessingService->processImage(
                            storage_path('app/public/' . str_replace('/storage/', '', $data['cover_image_url'])),
                            [
                                'resize' => ['width' => $imageWidth, 'height' => $imageHeight],
                                'optimize' => true,
                                'quality' => 85
                            ]
                        );

                        if ($processedImage['success']) {
                            // Update with processed image path if different
                            $data['cover_image_url'] = $processedImage['data']['url'] ?? $data['cover_image_url'];
                        }
                    } catch (\Exception $e) {
                        Log::warning('Image processing failed', [
                            'episode_title' => $data['title'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Auto-set premium status based on story premium status
            $story = Story::with('narrator')->find($request->story_id);
            if ($story && $story->is_premium) {
                // If story is premium, all episodes must be premium
                $data['is_premium'] = true;
            } else {
                // If story is free, use the value from request (can be premium or free)
                $data['is_premium'] = $request->boolean('is_premium', $episode->is_premium);
            }

            // Auto-set narrator from story narrator (if story has a narrator)
            // All episodes of a story should have the same narrator as the story
            $personNarrator = $story ? $story->getMatchingPersonNarrator() : null;
            if ($personNarrator) {
                $data['narrator_id'] = $personNarrator->id;
            }

            $episode->update($data);

            // Update story statistics after updating episode
            if ($story) {
                $story->updateStatistics();
            }

            // Handle people relationships
            if ($request->filled('people')) {
                $episode->people()->sync($request->people);
            } else {
                $episode->people()->detach();
            }

            // Send notification if status changed to published - only to users who favorited this story
            if ($episode->status === 'published' && $episode->wasChanged('status')) {
                $story = $episode->story;
                $favoritedUserIds = \App\Models\Favorite::where('story_id', $story->id)
                    ->pluck('user_id')
                    ->toArray();

                if (!empty($favoritedUserIds)) {
                    $this->notificationService->sendToMultipleUsers(
                        $favoritedUserIds,
                        'content',
                        'قسمت جدید منتشر شد',
                        "قسمت جدید \"{$episode->title}\" از داستان \"{$story->title}\" منتشر شد.",
                        [
                            'is_important' => true,
                            'action_type' => 'button',
                            'action_text' => 'شنیدن قسمت',
                            'action_url' => '/episodes/latest',
                            'data' => [
                                'story_title' => $story->title,
                                'episode_title' => $episode->title,
                                'story_id' => $story->id,
                                'episode_id' => $episode->id
                            ]
                        ]
                    );
                }
            }

            DB::commit();

            return redirect()->route('admin.episodes.index')
                ->with('success', 'اپیزود با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update episode', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'خطا در به‌روزرسانی اپیزود: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Episode $episode)
    {
        try {
            DB::beginTransaction();

            // Delete associated files
            if ($episode->audio_url && file_exists(public_path($episode->audio_url))) {
                try {
                    unlink(public_path($episode->audio_url));
                    Log::info('Audio file deleted successfully', ['file' => $episode->audio_url]);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete audio file', [
                        'file' => $episode->audio_url,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($episode->cover_image_url && file_exists(public_path('images/' . $episode->cover_image_url))) {
                try {
                    unlink(public_path('images/' . $episode->cover_image_url));
                    Log::info('Cover image deleted successfully', ['file' => $episode->cover_image_url]);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete cover image', [
                        'file' => $episode->cover_image_url,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $episode->delete();

            DB::commit();

            return redirect()->route('admin.episodes.index')
                ->with('success', 'اپیزود با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete episode', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.episodes.index')
                ->with('error', 'خطا در حذف اپیزود: ' . $e->getMessage());
        }
    }

    /**
     * Bulk operations on episodes
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:publish,unpublish,delete,change_status,change_narrator,reorder',
            'episode_ids' => 'required|array|min:1',
            'episode_ids.*' => 'integer|exists:episodes,id',
            'status' => 'required_if:action,change_status|string|in:draft,published,archived',
            'narrator_id' => 'required_if:action,change_narrator|integer|exists:people,id',
            'order_data' => 'required_if:action,reorder|array'
        ], [
            'action.required' => 'عملیات الزامی است',
            'action.in' => 'عملیات نامعتبر است',
            'episode_ids.required' => 'انتخاب حداقل یک اپیزود الزامی است',
            'episode_ids.array' => 'شناسه‌های اپیزود باید آرایه باشند',
            'episode_ids.min' => 'حداقل یک اپیزود باید انتخاب شود',
            'episode_ids.*.exists' => 'یکی از اپیزودها یافت نشد',
            'status.required_if' => 'وضعیت الزامی است',
            'status.in' => 'وضعیت نامعتبر است',
            'narrator_id.required_if' => 'راوی الزامی است',
            'narrator_id.exists' => 'راوی یافت نشد',
            'order_data.required_if' => 'داده‌های ترتیب الزامی است',
            'order_data.array' => 'داده‌های ترتیب باید آرایه باشند'
        ]);

        try {
            DB::beginTransaction();

            $episodeIds = $request->episode_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($episodeIds as $episodeId) {
                try {
                    $episode = Episode::findOrFail($episodeId);

                    switch ($action) {
                        case 'publish':
                            $episode->update([
                                'status' => 'published',
                                'release_date' => now()
                            ]);
                            break;

                        case 'unpublish':
                            $episode->update([
                                'status' => 'draft',
                                'release_date' => null
                            ]);
                            break;

                        case 'delete':
                            // Delete associated files
                            if ($episode->audio_url && file_exists(public_path($episode->audio_url))) {
                                try {
                                    unlink(public_path($episode->audio_url));
                                    Log::info('Audio file deleted successfully in bulk action', ['file' => $episode->audio_url]);
                                } catch (\Exception $e) {
                                    Log::warning('Failed to delete audio file in bulk action', [
                                        'file' => $episode->audio_url,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                            if ($episode->cover_image_url && file_exists(public_path('images/' . $episode->cover_image_url))) {
                                try {
                                    unlink(public_path('images/' . $episode->cover_image_url));
                                    Log::info('Cover image deleted successfully in bulk action', ['file' => $episode->cover_image_url]);
                                } catch (\Exception $e) {
                                    Log::warning('Failed to delete cover image in bulk action', [
                                        'file' => $episode->cover_image_url,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                            $episode->delete();
                            break;

                        case 'change_status':
                            $episode->update(['status' => $request->status]);
                            break;

                        case 'change_narrator':
                            $episode->update(['narrator_id' => $request->narrator_id]);
                            break;

                        case 'reorder':
                            // Handle reordering logic
                            $orderData = $request->order_data;
                            foreach ($orderData as $orderItem) {
                                if (isset($orderItem['id']) && isset($orderItem['episode_number'])) {
                                    Episode::where('id', $orderItem['id'])
                                          ->update(['episode_number' => $orderItem['episode_number']]);
                                }
                            }
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for episode', [
                        'episode_id' => $episodeId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $message = "عملیات {$action} روی {$successCount} اپیزود انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} اپیزود ناموفق بود";
            }

            return redirect()->route('admin.episodes.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'episode_ids' => $request->episode_ids,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.episodes.index')
                ->with('error', 'خطا در انجام عملیات گروهی: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate an episode
     */
    public function duplicate(Episode $episode)
    {
        try {
            DB::beginTransaction();

            $newEpisode = $episode->replicate();
            $newEpisode->title = $episode->title . ' (کپی)';
            $newEpisode->status = 'draft';
            $newEpisode->release_date = null;
            $newEpisode->play_count = 0;
            $newEpisode->rating = 0;
            $newEpisode->audio_url = ''; // Set to empty string instead of null
            $newEpisode->cover_image_url = null;
            $newEpisode->save();

            // Copy people relationships
            $episode->people()->each(function($person) use ($newEpisode) {
                $newEpisode->people()->attach($person->id);
            });

            DB::commit();

            return redirect()->route('admin.episodes.edit', $newEpisode)
                ->with('success', 'اپیزود با موفقیت کپی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate episode', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.episodes.index')
                ->with('error', 'خطا در کپی کردن اپیزود: ' . $e->getMessage());
        }
    }

    /**
     * Export episodes
     */
    public function export(Request $request)
    {
        $query = Episode::with(['story', 'narrator']);

        // Apply same filters as index
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('story', function($storyQuery) use ($request) {
                      $storyQuery->where('title', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $episodes = $query->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'عنوان', 'توضیحات', 'شماره اپیزود', 'داستان', 'راوی', 'مدت زمان',
            'وضعیت', 'پریمیوم', 'امتیاز', 'تعداد پخش', 'تاریخ انتشار', 'تاریخ ایجاد'
        ];

        foreach ($episodes as $episode) {
            $csvData[] = [
                $episode->id,
                $episode->title,
                $episode->description,
                $episode->episode_number,
                $episode->story->title ?? '',
                $episode->narrator->name ?? '',
                gmdate('H:i:s', $episode->duration),
                $episode->status,
                $episode->is_premium ? 'بله' : 'خیر',
                $episode->rating,
                $episode->play_count,
                $episode->release_date ? $episode->release_date->format('Y-m-d H:i:s') : '',
                $episode->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'episodes_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get episode statistics
     */
    public function statistics()
    {
        $stats = [
            'total_episodes' => Episode::count(),
            'published_episodes' => Episode::where('status', 'published')->count(),
            'draft_episodes' => Episode::where('status', 'draft')->count(),
            'archived_episodes' => Episode::where('status', 'archived')->count(),
            'premium_episodes' => Episode::where('is_premium', true)->count(),
            'free_episodes' => Episode::where('is_premium', false)->count(),
            'total_duration' => Episode::sum('duration'),
            'avg_rating' => round(Episode::avg('rating'), 2),
            'total_plays' => Episode::sum('play_count'),
            'avg_duration' => round(Episode::avg('duration'), 2),
            'episodes_by_status' => Episode::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'episodes_by_story' => Episode::selectRaw('stories.title, COUNT(*) as count')
                ->join('stories', 'episodes.story_id', '=', 'stories.id')
                ->groupBy('stories.id', 'stories.title')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'recent_episodes' => Episode::with(['story', 'narrator'])
                ->latest()
                ->limit(10)
                ->get(),
            'top_rated_episodes' => Episode::with(['story', 'narrator'])
                ->orderBy('rating', 'desc')
                ->limit(10)
                ->get(),
            'most_played_episodes' => Episode::with(['story', 'narrator'])
                ->orderBy('play_count', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Publish episode and notify users
     */
    public function publish(Episode $episode)
    {
        try {
            DB::beginTransaction();

            $episode->update([
                'status' => 'published',
                'release_date' => now()
            ]);

            // Send notification to users who favorited this story about new episode
            $story = $episode->story;
            $favoritedUserIds = \App\Models\Favorite::where('story_id', $story->id)
                ->pluck('user_id')
                ->toArray();

            if (!empty($favoritedUserIds)) {
                $this->notificationService->sendToMultipleUsers(
                    $favoritedUserIds,
                    'content',
                    'قسمت جدید منتشر شد',
                    "قسمت جدید \"{$episode->title}\" از داستان \"{$story->title}\" منتشر شد.",
                    [
                        'is_important' => true,
                        'action_type' => 'button',
                        'action_text' => 'شنیدن قسمت',
                        'action_url' => '/episodes/latest',
                        'data' => [
                            'story_title' => $story->title,
                            'episode_title' => $episode->title,
                            'story_id' => $story->id,
                            'episode_id' => $episode->id
                        ]
                    ]
                );
            }

            // Notify voice actors about episode published
            $this->notifyEpisodePublished($episode);

            DB::commit();

            return redirect()->route('admin.episodes.index')
                ->with('success', 'اپیزود منتشر شد و کاربران مطلع شدند.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to publish episode', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.episodes.index')
                ->with('error', 'خطا در انتشار اپیزود: ' . $e->getMessage());
        }
    }

    /**
     * Reorder episodes for a story
     */
    public function reorder(Request $request, Story $story)
    {
        $request->validate([
            'episodes' => 'required|array',
            'episodes.*.id' => 'required|integer|exists:episodes,id',
            'episodes.*.episode_number' => 'required|integer|min:1'
        ], [
            'episodes.required' => 'لیست اپیزودها الزامی است',
            'episodes.array' => 'لیست اپیزودها باید آرایه باشد',
            'episodes.*.id.required' => 'شناسه اپیزود الزامی است',
            'episodes.*.id.exists' => 'اپیزود یافت نشد',
            'episodes.*.episode_number.required' => 'شماره اپیزود الزامی است',
            'episodes.*.episode_number.min' => 'شماره اپیزود باید حداقل 1 باشد'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->episodes as $episodeData) {
                Episode::where('id', $episodeData['id'])
                      ->where('story_id', $story->id)
                      ->update(['episode_number' => $episodeData['episode_number']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ترتیب اپیزودها با موفقیت به‌روزرسانی شد'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder episodes', [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی ترتیب اپیزودها: ' . $e->getMessage()
            ], 500);
        }
    }

    // API Methods for Postman Collection / Next dashboard
    public function apiIndex(Request $request)
    {
        $query = $this->buildEpisodeApiListQuery($request);
        $this->applyEpisodeListSort($query, $request);

        $perPage = $this->resolveEpisodeListPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->with(['story', 'voiceActors'])
            ->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildEpisodeApiListQuery($request);
        $this->applyEpisodeListSort($query, $request);

        $filename = 'episodes-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'story_id', 'title', 'episode_number', 'duration', 'status', 'is_premium', 'created_at']);

            $query->clone()->select(['id', 'story_id', 'title', 'episode_number', 'duration', 'status', 'is_premium', 'created_at'])
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->story_id,
                            $row->title,
                            $row->episode_number,
                            $row->duration,
                            $row->status,
                            $row->is_premium ? '1' : '0',
                            $row->created_at?->toIso8601String(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'story_id' => 'required|exists:stories,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'audio_file_url' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published,archived',
            'is_premium' => 'boolean',
            'age_rating' => 'required|in:all,3+,7+,12+,16+,18+',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $episode = Episode::create($this->prepareApiEpisodeAttributes($validated));

        if (! empty($validated['audio_file_url'])) {
            app(MediaLibraryService::class)->syncUsageFor($episode, 'audio_url', $validated['audio_file_url']);
        }

        return AdminApiResponse::success($this->formatApiEpisode($episode->load('story')), 'Episode created successfully', 201);
    }

    public function apiShow(Episode $episode)
    {
        return AdminApiResponse::success($this->formatApiEpisode($episode->load(['story', 'voiceActors'])));
    }

    public function apiUpdate(Request $request, Episode $episode)
    {
        $validated = $request->validate([
            'story_id' => 'sometimes|required|exists:stories,id',
            'title' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'sometimes|required|integer|min:1',
            'duration' => 'sometimes|required|integer|min:1',
            'audio_file_url' => 'nullable|string|max:500',
            'status' => 'sometimes|required|in:draft,published,archived',
            'is_premium' => 'boolean',
            'age_rating' => 'sometimes|required|in:all,3+,7+,12+,16+,18+',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $statusService = app(StoryEpisodeStatusService::class);

        if (array_key_exists('status', $validated)) {
            $newStatus = $validated['status'];
            unset($validated['status']);
            $attributes = $this->prepareApiEpisodeAttributes($validated, $episode);
            if ($attributes !== []) {
                $episode->update($attributes);
            }
            $statusService->applyEpisodeStatus($episode->fresh()->loadMissing('story'), $newStatus);
        } else {
            $episode->update($this->prepareApiEpisodeAttributes($validated, $episode));
        }

        if (array_key_exists('audio_file_url', $validated)) {
            app(MediaLibraryService::class)->syncUsageFor($episode, 'audio_url', $validated['audio_file_url'] ?: null);
        }

        return AdminApiResponse::success($this->formatApiEpisode($episode->load('story')), 'Episode updated successfully');
    }

    public function apiDestroy(Episode $episode)
    {
        $episode->delete();

        return AdminApiResponse::okMessage('Episode deleted successfully');
    }

    public function apiBulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,publish,archive,draft,unpublish,change_status',
            'status' => 'required_if:action,change_status|in:draft,published,archived',
            'episode_ids' => 'nullable|array',
            'episode_ids.*' => 'integer|exists:episodes,id',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:episodes,id',
        ]);

        $ids = $validated['episode_ids'] ?? $validated['selected_items'] ?? [];
        if (count($ids) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ اپیزودی انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $episodes = Episode::with('story')->whereIn('id', $ids)->get();
        $statusService = app(StoryEpisodeStatusService::class);

        switch ($validated['action']) {
            case 'delete':
                Episode::whereIn('id', $ids)->delete();
                $message = 'Episodes deleted successfully';
                break;
            case 'publish':
                $statusService->bulkApplyEpisodeStatus($episodes, 'published');
                $message = 'Episodes published successfully';
                break;
            case 'archive':
                $statusService->bulkApplyEpisodeStatus($episodes, 'archived');
                $message = 'Episodes archived successfully';
                break;
            case 'draft':
            case 'unpublish':
                $statusService->bulkApplyEpisodeStatus($episodes, 'draft');
                $message = 'Episodes moved to draft successfully';
                break;
            default:
                $statusService->bulkApplyEpisodeStatus($episodes, $validated['status']);
                $message = 'Episode status updated successfully';
                break;
        }

        return AdminApiResponse::okMessage($message);
    }

    public function apiPublish(Episode $episode)
    {
        try {
            app(StoryEpisodeStatusService::class)->applyEpisodeStatus($episode->loadMissing('story'), 'published');

            return AdminApiResponse::success($episode->fresh(['story']), 'Episode published successfully');
        } catch (\Exception $e) {
            Log::error('Failed to publish episode via API', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to publish episode',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiDuplicate(Episode $episode)
    {
        try {
            $newEpisode = $episode->replicate();
            $newEpisode->title = $episode->title . ' (کپی)';
            $newEpisode->status = 'draft';
            $newEpisode->release_date = null;
            $newEpisode->episode_number = (int) $episode->episode_number + 1;
            $newEpisode->save();

            return AdminApiResponse::success($newEpisode->load(['story']), 'Episode duplicated successfully', 201);
        } catch (\Exception $e) {
            Log::error('Failed to duplicate episode via API', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate episode',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiReorder(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'episodes' => 'required|array',
            'episodes.*.id' => 'required|integer|exists:episodes,id',
            'episodes.*.episode_number' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->episodes as $episodeData) {
                Episode::where('id', $episodeData['id'])
                    ->where('story_id', $request->story_id)
                    ->update(['episode_number' => $episodeData['episode_number']]);
            }
            DB::commit();

            return AdminApiResponse::okMessage('Episodes reordered successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder episodes via API', [
                'story_id' => $request->story_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder episodes',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiStatistics()
    {
        $stats = [
            'total_episodes' => Episode::count(),
            'published_episodes' => Episode::where('status', 'published')->count(),
            'draft_episodes' => Episode::where('status', 'draft')->count(),
            'archived_episodes' => Episode::where('status', 'archived')->count(),
            'premium_episodes' => Episode::where('is_premium', true)->count(),
            'free_episodes' => Episode::where('is_premium', false)->count(),
            'total_duration' => Episode::sum('duration'),
            'average_duration' => Episode::avg('duration'),
            'episodes_by_story' => Episode::with('story')
                ->selectRaw('story_id, count(*) as count')
                ->groupBy('story_id')
                ->get(),
        ];

        return AdminApiResponse::success($stats);
    }

    private function buildEpisodeApiListQuery(Request $request)
    {
        $query = Episode::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('story_id')) {
            $query->where('story_id', $request->input('story_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_premium')) {
            $val = $request->input('is_premium');
            $query->where('is_premium', filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $val);
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', Carbon::now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', Carbon::now()->subMonth());
                    break;
                case 'year':
                    $query->where('created_at', '>=', Carbon::now()->subYear());
                    break;
            }
        }

        return $query;
    }

    private function applyEpisodeListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'episode_number');
        $sortDir = strtolower((string) $request->input('sortDir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['episode_number', 'created_at', 'id', 'title', 'duration', 'status', 'story_id'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'episode_number';
            $sortDir = 'asc';
        }
        $query->orderBy($sortBy, $sortDir);
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'asc');
        }
    }

    private function resolveEpisodeListPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 15));

        return min(100, max(1, $raw ?: 15));
    }

    /**
     * Notify voice actors when episode is published
     */
    private function notifyEpisodePublished(Episode $episode): void
    {
        try {
            $story = $episode->story;

            // Notify episode narrator (if assigned and is a User)
            if ($episode->narrator_id) {
                // Note: narrator_id in episodes table references people table, not users
                // We need to check if there's a corresponding user
                $narratorPerson = Person::find($episode->narrator_id);
                if ($narratorPerson && isset($narratorPerson->email)) {
                    $narratorUser = User::where('email', $narratorPerson->email)->first();
                    if ($narratorUser) {
                        $this->pushNotificationService->sendContentPublishedNotification(
                            $narratorUser,
                            'episode',
                            [
                                'episode_id' => $episode->id,
                                'episode_title' => $episode->title,
                                'story_id' => $story->id,
                                'story_title' => $story->title ?? 'داستان',
                                'role' => 'narrator'
                            ]
                        );
                    }
                }
            }

            // Notify episode voice actors
            $voiceActors = $episode->voiceActors;
            foreach ($voiceActors as $voiceActor) {
                $person = $voiceActor->person;
                if ($person && isset($person->email)) {
                    $user = User::where('email', $person->email)->first();
                    if ($user) {
                        $this->pushNotificationService->sendContentPublishedNotification(
                            $user,
                            'episode',
                            [
                                'episode_id' => $episode->id,
                                'episode_title' => $episode->title,
                                'story_id' => $story->id,
                                'story_title' => $story->title ?? 'داستان',
                                'role' => 'voice_actor',
                                'character_name' => $voiceActor->character_name,
                                'start_time' => $voiceActor->start_time,
                                'end_time' => $voiceActor->end_time
                            ]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify voice actors about episode published', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepareApiEpisodeAttributes(array $validated, ?Episode $existing = null): array
    {
        $attributes = $validated;
        unset($attributes['audio_file_url']);

        if (array_key_exists('audio_file_url', $validated)) {
            $attributes['audio_url'] = $validated['audio_file_url'] ?: ($existing?->getRawOriginal('audio_url') ?? '');
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatApiEpisode(Episode $episode): array
    {
        $data = $episode->toArray();
        $data['audio_file_url'] = $episode->audio_url;

        return $data;
    }
}

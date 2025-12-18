<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Episode;
use App\Models\Story;
use App\Models\Person;
use App\Services\InAppNotificationService;
use App\Services\AudioProcessingService;
use App\Services\ImageProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EpisodeController extends BaseController
{
    protected $notificationService;
    protected $audioProcessingService;
    protected $imageProcessingService;

    public function __construct(
        InAppNotificationService $notificationService,
        AudioProcessingService $audioProcessingService,
        ImageProcessingService $imageProcessingService
    ) {
        $this->notificationService = $notificationService;
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
        $stories = Story::with('category')->orderBy('title', 'asc')->get();
        $narrators = Person::whereJsonContains('roles', 'narrator')->get();
        $people = Person::orderBy('name', 'asc')->get();

        return view('admin.episodes.create', compact('stories', 'narrators', 'people'));
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

            $episode = Episode::create($data);

            // Attach people if provided
            if ($request->filled('people')) {
                $episode->people()->attach($request->people);
            }

            // Send notification if published
            if ($episode->status === 'published') {
                $this->notificationService->sendToAllUsers(
                    'content',
                    'قسمت جدید منتشر شد',
                    "قسمت جدید \"{$episode->title}\" از داستان \"{$episode->story->title}\" منتشر شد.",
                    [
                        'is_important' => true,
                        'action_type' => 'button',
                        'action_text' => 'شنیدن قسمت',
                        'action_url' => '/episodes/latest',
                        'data' => ['story_title' => $episode->story->title, 'episode_title' => $episode->title]
                    ]
                );
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

        return view('admin.episodes.edit', compact('episode', 'stories', 'narrators', 'people'));
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
            'episode_number' => 'required|integer|min:1',
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

            $episode->update($data);

            // Handle people relationships
            if ($request->filled('people')) {
                $episode->people()->sync($request->people);
            } else {
                $episode->people()->detach();
            }

            // Send notification if status changed to published
            if ($episode->status === 'published' && $episode->wasChanged('status')) {
                $this->notificationService->sendToAllUsers(
                    'content',
                    'قسمت جدید منتشر شد',
                    "قسمت جدید \"{$episode->title}\" از داستان \"{$episode->story->title}\" منتشر شد.",
                    [
                        'is_important' => true,
                        'action_type' => 'button',
                        'action_text' => 'شنیدن قسمت',
                        'action_url' => '/episodes/latest',
                        'data' => ['story_title' => $episode->story->title, 'episode_title' => $episode->title]
                    ]
                );
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

            // Send notification to all users about new episode
            $this->notificationService->sendToAllUsers(
                'content',
                'قسمت جدید منتشر شد',
                "قسمت جدید \"{$episode->title}\" از داستان \"{$episode->story->title}\" منتشر شد.",
                [
                    'is_important' => true,
                    'action_type' => 'button',
                    'action_text' => 'شنیدن قسمت',
                    'action_url' => '/episodes/latest',
                    'data' => ['story_title' => $episode->story->title, 'episode_title' => $episode->title]
                ]
            );

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

    // API Methods for Postman Collection
    public function apiIndex(Request $request)
    {
        $episodes = Episode::with(['story', 'voiceActors'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->story_id, function ($query, $storyId) {
                $query->where('story_id', $storyId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('episode_number', 'asc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $episodes
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'audio_file_url' => 'nullable|url|max:500',
            'status' => 'required|in:draft,published,archived',
            'is_premium' => 'boolean',
            'age_rating' => 'required|in:all,3+,7+,12+,16+,18+',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $episode = Episode::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Episode created successfully',
            'data' => $episode->load('story')
        ], 201);
    }

    public function apiShow(Episode $episode)
    {
        return response()->json([
            'success' => true,
            'data' => $episode->load(['story', 'voiceActors'])
        ]);
    }

    public function apiUpdate(Request $request, Episode $episode)
    {
        $request->validate([
            'story_id' => 'sometimes|required|exists:stories,id',
            'title' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'sometimes|required|integer|min:1',
            'duration' => 'sometimes|required|integer|min:1',
            'audio_file_url' => 'nullable|url|max:500',
            'status' => 'sometimes|required|in:draft,published,archived',
            'is_premium' => 'boolean',
            'age_rating' => 'sometimes|required|in:all,3+,7+,12+,16+,18+',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $episode->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Episode updated successfully',
            'data' => $episode->load('story')
        ]);
    }

    public function apiDestroy(Episode $episode)
    {
        $episode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Episode deleted successfully'
        ]);
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,publish,archive',
            'episode_ids' => 'required|array',
            'episode_ids.*' => 'exists:episodes,id'
        ]);

        $episodes = Episode::whereIn('id', $request->episode_ids);

        switch ($request->action) {
            case 'delete':
                $episodes->delete();
                $message = 'Episodes deleted successfully';
                break;
            case 'publish':
                $episodes->update(['status' => 'published', 'published_at' => now()]);
                $message = 'Episodes published successfully';
                break;
            case 'archive':
                $episodes->update(['status' => 'archived']);
                $message = 'Episodes archived successfully';
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
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
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}

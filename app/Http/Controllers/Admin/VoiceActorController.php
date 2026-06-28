<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Person;
use App\Models\Character;
use App\Models\Episode;
use App\Models\EpisodeVoiceActor;
use App\Services\UserStoryContributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VoiceActorController extends Controller
{
    /**
     * Display a listing of voice actors
     */
    public function index(Request $request)
    {
        $query = Person::whereJsonContains('roles', 'voice_actor');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('bio', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by verification status
        if ($request->has('verified') && $request->verified !== null) {
            $query->where('is_verified', $request->verified);
        }

        // Filter by activity status
        if ($request->has('active') && $request->active !== null) {
            $query->where('is_active', $request->active);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'total_episodes', 'total_stories', 'average_rating', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $voiceActors = $query->withCount(['episodeVoiceActors as total_episodes'])
            ->with(['episodeVoiceActors' => function($query) {
                $query->with('episode.story');
            }])
            ->paginate(20);

        // Get statistics
        $stats = [
            'total' => Person::whereJsonContains('roles', 'voice_actor')->count(),
            'verified' => Person::whereJsonContains('roles', 'voice_actor')->where('is_verified', true)->count(),
            'unverified' => Person::whereJsonContains('roles', 'voice_actor')->where('is_verified', false)->count(),
            'active' => Person::whereJsonContains('roles', 'voice_actor')->where('is_active', true)->count(),
            'total_episodes' => EpisodeVoiceActor::count(),
            'total_stories' => EpisodeVoiceActor::distinct('episode_id')->count(),
        ];

        return view('admin.voice-actors.index', compact('voiceActors', 'stats'));
    }

    /**
     * Show the form for creating a new voice actor
     */
    public function create()
    {
        return view('admin.voice-actors.create');
    }

    /**
     * Store a newly created voice actor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'voice_type' => 'nullable|string|max:100',
            'voice_range' => 'nullable|string|max:100',
            'specialties' => 'nullable|array',
            'specialties.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'experience_years' => 'nullable|integer|min:0|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_verified' => 'boolean',
            'is_active' => 'boolean'
        ], [
            'name.required' => 'نام الزامی است',
            'name.max' => 'نام نمی‌تواند بیش از 100 کاراکتر باشد',
            'bio.max' => 'بیوگرافی نمی‌تواند بیش از 1000 کاراکتر باشد',
            'image.image' => 'فایل باید تصویر باشد',
            'image.mimes' => 'فرمت تصویر باید jpeg، png، jpg یا webp باشد',
            'image.max' => 'حجم تصویر نمی‌تواند بیش از 2 مگابایت باشد',
            'voice_type.max' => 'نوع صدا نمی‌تواند بیش از 100 کاراکتر باشد',
            'voice_range.max' => 'محدوده صدا نمی‌تواند بیش از 100 کاراکتر باشد',
            'specialties.array' => 'تخصص‌ها باید آرایه باشند',
            'specialties.*.max' => 'هر تخصص نمی‌تواند بیش از 100 کاراکتر باشد',
            'languages.array' => 'زبان‌ها باید آرایه باشند',
            'languages.*.max' => 'هر زبان نمی‌تواند بیش از 50 کاراکتر باشد',
            'experience_years.integer' => 'سال‌های تجربه باید عدد باشد',
            'experience_years.min' => 'سال‌های تجربه نمی‌تواند منفی باشد',
            'experience_years.max' => 'سال‌های تجربه نمی‌تواند بیش از 100 باشد',
            'hourly_rate.numeric' => 'نرخ ساعتی باید عدد باشد',
            'hourly_rate.min' => 'نرخ ساعتی نمی‌تواند منفی باشد'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $voiceActorData = $request->only([
            'name', 'bio', 'voice_type', 'voice_range', 'specialties', 
            'languages', 'experience_years', 'hourly_rate', 'is_verified', 'is_active'
        ]);
        
        $voiceActorData['is_verified'] = $request->boolean('is_verified', false);
        $voiceActorData['is_active'] = $request->boolean('is_active', true);
        $voiceActorData['roles'] = ['voice_actor'];

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('voice-actors', $imageName, 'public');
            $voiceActorData['image_path'] = $imagePath;
        }

        $voiceActor = Person::create($voiceActorData);

        return redirect()->route('admin.voice-actors.index')
            ->with('success', 'صداپیشه با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified voice actor
     */
    public function show(Person $voiceActor)
    {
        $voiceActor->load(['episodeVoiceActors' => function($query) {
            $query->with(['episode.story'])->orderBy('created_at', 'desc');
        }]);

        $stats = [
            'total_episodes' => $voiceActor->episodeVoiceActors->count(),
            'total_stories' => $voiceActor->episodeVoiceActors->pluck('episode.story_id')->unique()->count(),
            'total_hours' => $voiceActor->episodeVoiceActors->sum(function($va) {
                return ($va->end_time - $va->start_time) / 3600;
            }),
            'average_rating' => $voiceActor->episodeVoiceActors->avg('rating') ?? 0,
        ];

        return view('admin.voice-actors.show', compact('voiceActor', 'stats'));
    }

    /**
     * Show the form for editing the specified voice actor
     */
    public function edit(Person $voiceActor)
    {
        return view('admin.voice-actors.edit', compact('voiceActor'));
    }

    /**
     * Update the specified voice actor
     */
    public function update(Request $request, Person $voiceActor)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'voice_type' => 'nullable|string|max:100',
            'voice_range' => 'nullable|string|max:100',
            'specialties' => 'nullable|array',
            'specialties.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'experience_years' => 'nullable|integer|min:0|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_verified' => 'boolean',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $voiceActorData = $request->only([
            'name', 'bio', 'voice_type', 'voice_range', 'specialties', 
            'languages', 'experience_years', 'hourly_rate', 'is_verified', 'is_active'
        ]);
        
        $voiceActorData['is_verified'] = $request->boolean('is_verified', false);
        $voiceActorData['is_active'] = $request->boolean('is_active', true);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($voiceActor->image_path) {
                Storage::disk('public')->delete($voiceActor->image_path);
            }
            
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('voice-actors', $imageName, 'public');
            $voiceActorData['image_path'] = $imagePath;
        }

        $voiceActor->update($voiceActorData);

        return redirect()->route('admin.voice-actors.index')
            ->with('success', 'صداپیشه با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified voice actor
     */
    public function destroy(Person $voiceActor)
    {
        // Check if voice actor has episodes
        if ($voiceActor->episodeVoiceActors()->count() > 0) {
            return redirect()->back()
                ->with('error', 'نمی‌توان صداپیشه‌ای که در اپیزودها استفاده شده را حذف کرد.');
        }

        // Delete image
        if ($voiceActor->image_path) {
            Storage::disk('public')->delete($voiceActor->image_path);
        }

        $voiceActor->delete();

        return redirect()->route('admin.voice-actors.index')
            ->with('success', 'صداپیشه با موفقیت حذف شد.');
    }

    /**
     * Toggle verification status
     */
    public function toggleVerification(Person $voiceActor)
    {
        $voiceActor->update(['is_verified' => !$voiceActor->is_verified]);
        
        $status = $voiceActor->is_verified ? 'تأیید شد' : 'تأیید لغو شد';
        
        return response()->json([
            'success' => true,
            'message' => "صداپیشه {$status}",
            'newStatus' => $voiceActor->is_verified ? 'verified' : 'unverified'
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Person $voiceActor)
    {
        $voiceActor->update(['is_active' => !$voiceActor->is_active]);
        
        $status = $voiceActor->is_active ? 'فعال شد' : 'غیرفعال شد';
        
        return response()->json([
            'success' => true,
            'message' => "صداپیشه {$status}",
            'newStatus' => $voiceActor->is_active ? 'active' : 'inactive'
        ]);
    }

    /**
     * Duplicate voice actor
     */
    public function duplicate(Person $voiceActor)
    {
        $newVoiceActor = $voiceActor->replicate();
        $newVoiceActor->name = $voiceActor->name . ' (کپی)';
        $newVoiceActor->is_verified = false;
        $newVoiceActor->image_path = null; // Don't copy image
        $newVoiceActor->save();

        return response()->json([
            'success' => true,
            'message' => 'صداپیشه با موفقیت کپی شد'
        ]);
    }

    /**
     * Export voice actors
     */
    public function export()
    {
        $voiceActors = Person::whereJsonContains('roles', 'voice_actor')
            ->withCount(['episodeVoiceActors as total_episodes'])
            ->get();

        $csvData = [];
        $csvData[] = ['نام', 'بیوگرافی', 'نوع صدا', 'محدوده صدا', 'تخصص‌ها', 'زبان‌ها', 'سال‌های تجربه', 'نرخ ساعتی', 'وضعیت تأیید', 'وضعیت فعال', 'تعداد اپیزودها', 'تاریخ ایجاد'];

        foreach ($voiceActors as $voiceActor) {
            $csvData[] = [
                $voiceActor->name,
                $voiceActor->bio,
                $voiceActor->voice_type,
                $voiceActor->voice_range,
                implode(', ', $voiceActor->specialties ?? []),
                implode(', ', $voiceActor->languages ?? []),
                $voiceActor->experience_years,
                $voiceActor->hourly_rate,
                $voiceActor->is_verified ? 'تأیید شده' : 'تأیید نشده',
                $voiceActor->is_active ? 'فعال' : 'غیرفعال',
                $voiceActor->total_episodes,
                $voiceActor->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'voice_actors_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get voice actor statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Person::whereJsonContains('roles', 'voice_actor')->count(),
            'verified' => Person::whereJsonContains('roles', 'voice_actor')->where('is_verified', true)->count(),
            'unverified' => Person::whereJsonContains('roles', 'voice_actor')->where('is_verified', false)->count(),
            'active' => Person::whereJsonContains('roles', 'voice_actor')->where('is_active', true)->count(),
            'total_episodes' => EpisodeVoiceActor::count(),
            'total_stories' => EpisodeVoiceActor::distinct('episode_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:verify,unverify,activate,deactivate,delete',
            'voice_actor_ids' => 'required|array|min:1',
            'voice_actor_ids.*' => 'exists:people,id'
        ]);

        $voiceActorIds = $request->voice_actor_ids;
        $action = $request->action;
        $count = 0;

        switch ($action) {
            case 'verify':
                Person::whereIn('id', $voiceActorIds)->update(['is_verified' => true]);
                $count = count($voiceActorIds);
                $message = "{$count} صداپیشه تأیید شد";
                break;
                
            case 'unverify':
                Person::whereIn('id', $voiceActorIds)->update(['is_verified' => false]);
                $count = count($voiceActorIds);
                $message = "{$count} صداپیشه تأیید لغو شد";
                break;
                
            case 'activate':
                Person::whereIn('id', $voiceActorIds)->update(['is_active' => true]);
                $count = count($voiceActorIds);
                $message = "{$count} صداپیشه فعال شد";
                break;
                
            case 'deactivate':
                Person::whereIn('id', $voiceActorIds)->update(['is_active' => false]);
                $count = count($voiceActorIds);
                $message = "{$count} صداپیشه غیرفعال شد";
                break;
                
            case 'delete':
                // Check if any voice actor has episodes
                $voiceActorsWithEpisodes = Person::whereIn('id', $voiceActorIds)
                    ->whereHas('episodeVoiceActors')
                    ->count();
                    
                if ($voiceActorsWithEpisodes > 0) {
                    return redirect()->back()
                        ->with('error', 'نمی‌توان صداپیشه‌هایی که در اپیزودها استفاده شده‌اند را حذف کرد.');
                }
                
                Person::whereIn('id', $voiceActorIds)->delete();
                $count = count($voiceActorIds);
                $message = "{$count} صداپیشه حذف شد";
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    // API Methods — voice actors are users with the voice_actor role (see VoiceActorsManagementController)
    public function apiIndex(Request $request)
    {
        $query = $this->eligibleVoiceActorUsersQuery();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone_number', 'like', "%{$searchTerm}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('active')) {
            if ($request->boolean('active')) {
                $query->where('status', 'active');
            } else {
                $query->where('status', '!=', 'active');
            }
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $voiceActors = $query
            ->withCount([
                'characters as total_characters',
                'storiesAsNarrator as total_stories_narrated',
                'storiesAsAuthor as total_stories_authored',
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($voiceActors->items())->map(fn (User $user) => $this->formatVoiceActorUserForApi($user))->values(),
            'pagination' => [
                'current_page' => $voiceActors->currentPage(),
                'last_page' => $voiceActors->lastPage(),
                'per_page' => $voiceActors->perPage(),
                'total' => $voiceActors->total(),
            ],
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/|unique:users,phone_number',
            'password' => 'nullable|string|min:8',
            'status' => 'nullable|in:active,inactive,suspended,pending',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password'] ?? bin2hex(random_bytes(8))),
            'role' => User::ROLE_VOICE_ACTOR,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'صداپیشه با موفقیت ایجاد شد.',
            'data' => $this->formatVoiceActorUserForApi($user),
        ], 201);
    }

    public function apiShow(User $voiceActor)
    {
        $this->ensureEligibleVoiceActorUser($voiceActor);

        $voiceActor->load([
            'characters.story:id,title,status',
            'storiesAsNarrator:id,title,status',
            'storiesAsAuthor:id,title,status',
        ]);

        $contributions = app(UserStoryContributionService::class)->summarizeForUser($voiceActor);

        $stats = [
            'total_characters' => $voiceActor->characters()->count(),
            'total_stories_narrated' => $voiceActor->storiesAsNarrator()->count(),
            'total_stories_authored' => $voiceActor->storiesAsAuthor()->count(),
            'total_episodes' => Episode::whereHas('story', function ($q) use ($voiceActor) {
                $q->where('narrator_id', $voiceActor->id);
            })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge($this->formatVoiceActorUserForApi($voiceActor), [
                'stats' => $stats,
                'story_contributions' => $contributions,
                'characters' => $voiceActor->characters->map(fn ($character) => [
                    'id' => $character->id,
                    'name' => $character->name,
                    'story_id' => $character->story_id,
                    'story_title' => $character->story?->title,
                ])->values(),
            ]),
        ]);
    }

    public function apiUpdate(Request $request, User $voiceActor)
    {
        $this->ensureEligibleVoiceActorUser($voiceActor);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'phone_number' => 'sometimes|required|string|regex:/^09[0-9]{9}$/|unique:users,phone_number,'.$voiceActor->id,
            'status' => 'sometimes|required|in:active,inactive,suspended,pending',
            'role' => 'sometimes|required|in:'.User::ROLE_VOICE_ACTOR.','.User::ROLE_ADMIN.','.User::ROLE_SUPER_ADMIN,
        ]);

        $voiceActor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'صداپیشه با موفقیت به‌روزرسانی شد.',
            'data' => $this->formatVoiceActorUserForApi($voiceActor->fresh()),
        ]);
    }

    public function apiDestroy(User $voiceActor)
    {
        $this->ensureEligibleVoiceActorUser($voiceActor);

        if (in_array($voiceActor->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توان کاربران مدیر را از این بخش حذف کرد.',
            ], 403);
        }

        if ($voiceActor->characters()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توان صداپیشه‌ای که شخصیت دارد را حذف کرد. ابتدا شخصیت‌ها را جدا کنید.',
            ], 422);
        }

        $voiceActor->delete();

        return response()->json([
            'success' => true,
            'message' => 'صداپیشه با موفقیت حذف شد.',
        ]);
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:verify,unverify,activate,deactivate,delete',
            'voice_actor_ids' => 'required|array|min:1',
            'voice_actor_ids.*' => 'exists:users,id',
        ]);

        $ids = $request->voice_actor_ids;
        $action = $request->action;
        $query = User::whereIn('id', $ids)->whereIn('role', [
            User::ROLE_VOICE_ACTOR,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN,
        ]);

        switch ($action) {
            case 'verify':
                $query->update(['phone_verified_at' => now()]);
                break;
            case 'unverify':
                $query->update(['phone_verified_at' => null]);
                break;
            case 'activate':
                $query->update(['status' => 'active']);
                break;
            case 'deactivate':
                $query->update(['status' => 'inactive']);
                break;
            case 'delete':
                User::whereIn('id', $ids)
                    ->where('role', User::ROLE_VOICE_ACTOR)
                    ->whereDoesntHave('characters')
                    ->delete();
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'عملیات با موفقیت انجام شد',
        ]);
    }

    public function apiStatistics()
    {
        $stats = [
            'total' => User::where('role', User::ROLE_VOICE_ACTOR)->count(),
            'verified' => User::where('role', User::ROLE_VOICE_ACTOR)->whereNotNull('phone_verified_at')->count(),
            'active' => User::where('role', User::ROLE_VOICE_ACTOR)->where('status', 'active')->count(),
            'total_episodes' => Character::whereNotNull('voice_actor_id')->distinct('story_id')->count('story_id'),
            'total_characters_assigned' => Character::whereNotNull('voice_actor_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function apiExport(Request $request)
    {
        $query = $this->eligibleVoiceActorUsersQuery();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $filename = 'voice-actors-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'first_name', 'last_name', 'phone_number', 'role', 'status', 'characters_count', 'created_at']);

            $query->withCount('characters')->orderBy('created_at', 'desc')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->first_name,
                        $row->last_name,
                        $row->phone_number,
                        $row->role,
                        $row->status,
                        $row->characters_count,
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function eligibleVoiceActorUsersQuery()
    {
        return User::query()->whereIn('role', [
            User::ROLE_VOICE_ACTOR,
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN,
        ]);
    }

    private function ensureEligibleVoiceActorUser(User $user): void
    {
        if (! in_array($user->role, [User::ROLE_VOICE_ACTOR, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            abort(404);
        }
    }

    private function formatVoiceActorUserForApi(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'status' => $user->status,
            'is_active' => $user->status === 'active',
            'is_verified' => $user->phone_verified_at !== null,
            'total_characters' => $user->total_characters ?? null,
            'total_stories_narrated' => $user->total_stories_narrated ?? null,
            'total_stories_authored' => $user->total_stories_authored ?? null,
            'profile_image_url' => $user->profile_image_url ?? null,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}

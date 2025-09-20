<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\EpisodeVoiceActor;
use App\Models\Episode;
use Illuminate\Http\Request;
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
}

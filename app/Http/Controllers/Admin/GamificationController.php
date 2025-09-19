<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gamification;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GamificationController extends Controller
{
    /**
     * Display a listing of gamification elements.
     */
    public function index(Request $request)
    {
        $query = Gamification::with(['story', 'episode']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('story', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('episode', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by story
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        // Filter by episode
        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
        }

        $gamifications = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => Gamification::count(),
            'active' => Gamification::where('is_active', true)->count(),
            'achievements' => Gamification::where('type', 'achievement')->count(),
            'badges' => Gamification::where('type', 'badge')->count(),
            'levels' => Gamification::where('type', 'level')->count(),
            'rewards' => Gamification::where('type', 'reward')->count(),
        ];

        $stories = Story::where('is_active', true)->get();
        $episodes = Episode::where('is_active', true)->get();

        return view('admin.gamification.index', compact('gamifications', 'stats', 'stories', 'episodes'));
    }

    /**
     * Show the form for creating a new gamification element.
     */
    public function create()
    {
        $stories = Story::where('is_active', true)->get();
        $episodes = Episode::where('is_active', true)->get();
        return view('admin.gamification.create', compact('stories', 'episodes'));
    }

    /**
     * Store a newly created gamification element.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:achievement,badge,level,reward,challenge',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'points_required' => 'required|integer|min:0',
            'reward_points' => 'required|integer|min:0',
            'reward_coins' => 'required|integer|min:0',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'badge_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'conditions' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'points_required' => $request->points_required,
                'reward_points' => $request->reward_points,
                'reward_coins' => $request->reward_coins,
                'conditions' => $request->conditions ? json_decode($request->conditions, true) : null,
                'is_active' => $request->has('is_active'),
            ];

            // Handle icon upload
            if ($request->hasFile('icon')) {
                $iconPath = $request->file('icon')->store('gamification/icons', 'public');
                $data['icon'] = $iconPath;
            }

            // Handle badge image upload
            if ($request->hasFile('badge_image')) {
                $badgePath = $request->file('badge_image')->store('gamification/badges', 'public');
                $data['badge_image'] = $badgePath;
            }

            $gamification = Gamification::create($data);

            DB::commit();

            return redirect()->route('admin.gamification.index')
                ->with('success', 'عنصر گیمیفیکیشن با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در ایجاد عنصر گیمیفیکیشن: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified gamification element.
     */
    public function show(Gamification $gamification)
    {
        $gamification->load(['story', 'episode']);
        
        // Get user achievements for this gamification element
        $userAchievements = DB::table('user_gamifications')
            ->where('gamification_id', $gamification->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.gamification.show', compact('gamification', 'userAchievements'));
    }

    /**
     * Show the form for editing the specified gamification element.
     */
    public function edit(Gamification $gamification)
    {
        $stories = Story::where('is_active', true)->get();
        $episodes = Episode::where('is_active', true)->get();
        return view('admin.gamification.edit', compact('gamification', 'stories', 'episodes'));
    }

    /**
     * Update the specified gamification element.
     */
    public function update(Request $request, Gamification $gamification)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:achievement,badge,level,reward,challenge',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'points_required' => 'required|integer|min:0',
            'reward_points' => 'required|integer|min:0',
            'reward_coins' => 'required|integer|min:0',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'badge_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'conditions' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'points_required' => $request->points_required,
                'reward_points' => $request->reward_points,
                'reward_coins' => $request->reward_coins,
                'conditions' => $request->conditions ? json_decode($request->conditions, true) : null,
                'is_active' => $request->has('is_active'),
            ];

            // Handle icon upload
            if ($request->hasFile('icon')) {
                // Delete old icon
                if ($gamification->icon) {
                    Storage::disk('public')->delete($gamification->icon);
                }
                $iconPath = $request->file('icon')->store('gamification/icons', 'public');
                $data['icon'] = $iconPath;
            }

            // Handle badge image upload
            if ($request->hasFile('badge_image')) {
                // Delete old badge image
                if ($gamification->badge_image) {
                    Storage::disk('public')->delete($gamification->badge_image);
                }
                $badgePath = $request->file('badge_image')->store('gamification/badges', 'public');
                $data['badge_image'] = $badgePath;
            }

            $gamification->update($data);

            DB::commit();

            return redirect()->route('admin.gamification.index')
                ->with('success', 'عنصر گیمیفیکیشن با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در به‌روزرسانی عنصر گیمیفیکیشن: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified gamification element.
     */
    public function destroy(Gamification $gamification)
    {
        try {
            // Delete associated files
            if ($gamification->icon) {
                Storage::disk('public')->delete($gamification->icon);
            }
            if ($gamification->badge_image) {
                Storage::disk('public')->delete($gamification->badge_image);
            }

            $gamification->delete();
            return redirect()->route('admin.gamification.index')
                ->with('success', 'عنصر گیمیفیکیشن با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف عنصر گیمیفیکیشن: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle active status.
     */
    public function toggle(Gamification $gamification)
    {
        try {
            $gamification->update(['is_active' => !$gamification->is_active]);
            
            $status = $gamification->is_active ? 'فعال' : 'غیرفعال';
            return redirect()->back()
                ->with('success', "عنصر گیمیفیکیشن {$status} شد.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در تغییر وضعیت: ' . $e->getMessage()]);
        }
    }

    /**
     * Duplicate a gamification element.
     */
    public function duplicate(Gamification $gamification)
    {
        try {
            DB::beginTransaction();

            $newGamification = $gamification->replicate();
            $newGamification->title = $gamification->title . ' (کپی)';
            $newGamification->is_active = false;
            $newGamification->save();

            DB::commit();

            return redirect()->route('admin.gamification.edit', $newGamification)
                ->with('success', 'عنصر گیمیفیکیشن با موفقیت کپی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در کپی کردن عنصر گیمیفیکیشن: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on gamification elements.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'gamification_ids' => 'required|array|min:1',
            'gamification_ids.*' => 'exists:gamifications,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک عنصر را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $gamifications = Gamification::whereIn('id', $request->gamification_ids);

            switch ($request->action) {
                case 'activate':
                    $gamifications->update(['is_active' => true]);
                    break;

                case 'deactivate':
                    $gamifications->update(['is_active' => false]);
                    break;

                case 'delete':
                    // Delete associated files
                    foreach ($gamifications->get() as $gamification) {
                        if ($gamification->icon) {
                            Storage::disk('public')->delete($gamification->icon);
                        }
                        if ($gamification->badge_image) {
                            Storage::disk('public')->delete($gamification->badge_image);
                        }
                    }
                    $gamifications->delete();
                    break;
            }

            DB::commit();

            $actionLabels = [
                'activate' => 'فعال‌سازی',
                'deactivate' => 'غیرفعال‌سازی',
                'delete' => 'حذف',
            ];

            return redirect()->back()
                ->with('success', 'عملیات ' . $actionLabels[$request->action] . ' با موفقیت انجام شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در انجام عملیات: ' . $e->getMessage()]);
        }
    }

    /**
     * Export gamification data.
     */
    public function export(Request $request)
    {
        $query = Gamification::with(['story', 'episode']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $gamifications = $query->orderBy('created_at', 'desc')->get();

        $filename = 'gamification_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($gamifications) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'عنوان',
                'نوع',
                'توضیحات',
                'داستان',
                'اپیزود',
                'امتیاز مورد نیاز',
                'پاداش امتیاز',
                'پاداش سکه',
                'وضعیت',
                'تاریخ ایجاد'
            ]);

            foreach ($gamifications as $gamification) {
                fputcsv($file, [
                    $gamification->title,
                    $this->getTypeLabel($gamification->type),
                    $gamification->description,
                    $gamification->story ? $gamification->story->title : '-',
                    $gamification->episode ? $gamification->episode->title : '-',
                    $gamification->points_required,
                    $gamification->reward_points,
                    $gamification->reward_coins,
                    $gamification->is_active ? 'فعال' : 'غیرفعال',
                    $gamification->created_at->format('Y/m/d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get gamification statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_gamifications' => Gamification::count(),
            'active_gamifications' => Gamification::where('is_active', true)->count(),
            'by_type' => Gamification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'by_story' => Gamification::selectRaw('story_id, COUNT(*) as count')
                ->with('story')
                ->groupBy('story_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'total_rewards_given' => DB::table('user_gamifications')->sum('reward_points'),
            'total_coins_given' => DB::table('user_gamifications')->sum('reward_coins'),
            'top_achievers' => DB::table('user_gamifications')
                ->selectRaw('user_id, COUNT(*) as achievement_count')
                ->selectRaw('SUM(reward_points) as total_points')
                ->selectRaw('SUM(reward_coins) as total_coins')
                ->with(['user'])
                ->groupBy('user_id')
                ->orderBy('achievement_count', 'desc')
                ->limit(10)
                ->get(),
        ];

        return view('admin.gamification.statistics', compact('stats'));
    }

    /**
     * Get type label.
     */
    private function getTypeLabel($type)
    {
        $labels = [
            'achievement' => 'دستاورد',
            'badge' => 'نشان',
            'level' => 'سطح',
            'reward' => 'پاداش',
            'challenge' => 'چالش',
        ];

        return $labels[$type] ?? $type;
    }
}

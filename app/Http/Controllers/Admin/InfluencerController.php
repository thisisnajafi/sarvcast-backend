<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfluencerCampaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InfluencerController extends Controller
{
    /**
     * Display a listing of influencer campaigns
     */
    public function index(Request $request): View
    {
        $query = InfluencerCampaign::with('user');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->filled('search')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%');
            })->orWhere('platform_username', 'like', '%' . $request->search . '%')
              ->orWhere('campaign_name', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch ($sortBy) {
            case 'name':
                $query->join('users', 'influencer_campaigns.user_id', '=', 'users.id')
                      ->orderBy('users.first_name', $sortDirection)
                      ->orderBy('users.last_name', $sortDirection);
                break;
            case 'platform':
                $query->orderBy('platform', $sortDirection);
                break;
            case 'followers':
                $query->orderBy('follower_count', $sortDirection);
                break;
            case 'status':
                $query->orderBy('status', $sortDirection);
                break;
            case 'verified':
                $query->orderBy('is_verified', $sortDirection);
                break;
            default:
                $query->orderBy('created_at', $sortDirection);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $influencers = $query->paginate($perPage);
        
        // Get statistics
        $stats = [
            'total' => InfluencerCampaign::count(),
            'verified' => InfluencerCampaign::where('is_verified', true)->count(),
            'pending' => InfluencerCampaign::where('status', 'pending')->count(),
            'active' => InfluencerCampaign::where('status', 'active')->count(),
            'suspended' => InfluencerCampaign::where('status', 'suspended')->count(),
            'expired' => InfluencerCampaign::where('expires_at', '<', now())->count(),
            'total_followers' => InfluencerCampaign::sum('follower_count'),
            'total_engagement' => InfluencerCampaign::sum('engagement_rate'),
        ];

        return view('admin.influencers.index', compact('influencers', 'stats'));
    }

    /**
     * Show the form for creating a new influencer campaign
     */
    public function create(): View
    {
        $users = User::where('role', 'parent')->get();
        return view('admin.influencers.create', compact('users'));
    }

    /**
     * Store a newly created influencer campaign
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'campaign_name' => 'required|string|max:255',
            'platform' => 'required|string|in:instagram,youtube,tiktok,twitter,facebook,linkedin,other',
            'platform_username' => 'required|string|max:255',
            'platform_url' => 'nullable|url|max:500',
            'follower_count' => 'required|integer|min:0',
            'engagement_rate' => 'required|numeric|min:0|max:100',
            'content_type' => 'required|string|in:story,post,video,live,reel,other',
            'target_audience' => 'required|string|max:500',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|string|in:pending,active,suspended,expired',
            'verification_documents' => 'nullable|array',
            'verification_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            DB::beginTransaction();

            // Handle file uploads
            if ($request->hasFile('verification_documents')) {
                $documents = [];
                foreach ($request->file('verification_documents') as $file) {
                    $path = $file->store('influencer-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $influencer = InfluencerCampaign::create($validated);

            DB::commit();

            return redirect()->route('admin.influencers.index')
                           ->with('success', 'کمپین اینفلوئنسر با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating influencer campaign: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در ایجاد کمپین اینفلوئنسر. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Display the specified influencer campaign
     */
    public function show(InfluencerCampaign $influencer): View
    {
        $influencer->load('user', 'campaignPosts');
        return view('admin.influencers.show', compact('influencer'));
    }

    /**
     * Show the form for editing the specified influencer campaign
     */
    public function edit(InfluencerCampaign $influencer): View
    {
        $users = User::where('role', 'parent')->get();
        return view('admin.influencers.edit', compact('influencer', 'users'));
    }

    /**
     * Update the specified influencer campaign
     */
    public function update(Request $request, InfluencerCampaign $influencer): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'campaign_name' => 'required|string|max:255',
            'platform' => 'required|string|in:instagram,youtube,tiktok,twitter,facebook,linkedin,other',
            'platform_username' => 'required|string|max:255',
            'platform_url' => 'nullable|url|max:500',
            'follower_count' => 'required|integer|min:0',
            'engagement_rate' => 'required|numeric|min:0|max:100',
            'content_type' => 'required|string|in:story,post,video,live,reel,other',
            'target_audience' => 'required|string|max:500',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|string|in:pending,active,suspended,expired',
            'verification_documents' => 'nullable|array',
            'verification_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            DB::beginTransaction();

            // Handle file uploads
            if ($request->hasFile('verification_documents')) {
                $documents = [];
                foreach ($request->file('verification_documents') as $file) {
                    $path = $file->store('influencer-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $influencer->update($validated);

            DB::commit();

            return redirect()->route('admin.influencers.index')
                           ->with('success', 'کمپین اینفلوئنسر با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating influencer campaign: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در به‌روزرسانی کمپین اینفلوئنسر. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Remove the specified influencer campaign
     */
    public function destroy(InfluencerCampaign $influencer): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Delete verification documents
            if ($influencer->verification_documents) {
                foreach ($influencer->verification_documents as $document) {
                    Storage::disk('public')->delete($document);
                }
            }

            $influencer->delete();

            DB::commit();

            return redirect()->route('admin.influencers.index')
                           ->with('success', 'کمپین اینفلوئنسر با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting influencer campaign: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در حذف کمپین اینفلوئنسر. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Verify an influencer campaign
     */
    public function verify(InfluencerCampaign $influencer): RedirectResponse
    {
        try {
            $influencer->update([
                'is_verified' => true,
                'verified_at' => now(),
                'status' => 'active'
            ]);

            return redirect()->back()
                           ->with('success', 'کمپین اینفلوئنسر با موفقیت تأیید شد.');

        } catch (\Exception $e) {
            Log::error('Error verifying influencer campaign: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تأیید کمپین اینفلوئنسر. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Suspend an influencer campaign
     */
    public function suspend(InfluencerCampaign $influencer): RedirectResponse
    {
        try {
            $influencer->update(['status' => 'suspended']);

            return redirect()->back()
                           ->with('success', 'کمپین اینفلوئنسر با موفقیت معلق شد.');

        } catch (\Exception $e) {
            Log::error('Error suspending influencer campaign: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تعلیق کمپین اینفلوئنسر. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Activate an influencer campaign
     */
    public function activate(InfluencerCampaign $influencer): RedirectResponse
    {
        try {
            $influencer->update(['status' => 'active']);

            return redirect()->back()
                           ->with('success', 'کمپین اینفلوئنسر با موفقیت فعال شد.');

        } catch (\Exception $e) {
            Log::error('Error activating influencer campaign: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در فعال‌سازی کمپین اینفلوئنسر. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string|in:verify,suspend,activate,delete',
            'influencer_ids' => 'required|array|min:1',
            'influencer_ids.*' => 'exists:influencer_campaigns,id'
        ]);

        try {
            DB::beginTransaction();

            $influencers = InfluencerCampaign::whereIn('id', $request->influencer_ids);

            switch ($request->action) {
                case 'verify':
                    $influencers->update([
                        'is_verified' => true,
                        'verified_at' => now(),
                        'status' => 'active'
                    ]);
                    $message = 'کمپین‌های اینفلوئنسر انتخاب شده با موفقیت تأیید شدند.';
                    break;

                case 'suspend':
                    $influencers->update(['status' => 'suspended']);
                    $message = 'کمپین‌های اینفلوئنسر انتخاب شده با موفقیت معلق شدند.';
                    break;

                case 'activate':
                    $influencers->update(['status' => 'active']);
                    $message = 'کمپین‌های اینفلوئنسر انتخاب شده با موفقیت فعال شدند.';
                    break;

                case 'delete':
                    // Delete verification documents
                    foreach ($influencers->get() as $influencer) {
                        if ($influencer->verification_documents) {
                            foreach ($influencer->verification_documents as $document) {
                                Storage::disk('public')->delete($document);
                            }
                        }
                    }
                    $influencers->delete();
                    $message = 'کمپین‌های اینفلوئنسر انتخاب شده با موفقیت حذف شدند.';
                    break;
            }

            DB::commit();

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk action: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در انجام عملیات گروهی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Export influencer campaigns
     */
    public function export(Request $request)
    {
        // Implementation for exporting influencer campaigns
        // This would typically generate a CSV or Excel file
        return response()->json(['message' => 'Export functionality will be implemented']);
    }

    /**
     * Get influencer statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => InfluencerCampaign::count(),
            'verified' => InfluencerCampaign::where('is_verified', true)->count(),
            'pending' => InfluencerCampaign::where('status', 'pending')->count(),
            'active' => InfluencerCampaign::where('status', 'active')->count(),
            'suspended' => InfluencerCampaign::where('status', 'suspended')->count(),
            'expired' => InfluencerCampaign::where('expires_at', '<', now())->count(),
            'total_followers' => InfluencerCampaign::sum('follower_count'),
            'total_engagement' => InfluencerCampaign::sum('engagement_rate'),
            'by_platform' => InfluencerCampaign::selectRaw('platform, COUNT(*) as count')
                                               ->groupBy('platform')
                                               ->get(),
            'by_status' => InfluencerCampaign::selectRaw('status, COUNT(*) as count')
                                            ->groupBy('status')
                                            ->get(),
        ];

        return response()->json($stats);
    }
}

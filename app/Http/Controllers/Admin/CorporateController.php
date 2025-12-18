<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorporateSponsorship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CorporateController extends Controller
{
    /**
     * Display a listing of corporate sponsorships
     */
    public function index(Request $request): View
    {
        $query = CorporateSponsorship::with('user');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sponsorship_type')) {
            $query->where('sponsorship_type', $request->sponsorship_type);
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
            })->orWhere('company_name', 'like', '%' . $request->search . '%')
              ->orWhere('company_address', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch ($sortBy) {
            case 'name':
                $query->join('users', 'corporate_sponsorships.user_id', '=', 'users.id')
                      ->orderBy('users.first_name', $sortDirection)
                      ->orderBy('users.last_name', $sortDirection);
                break;
            case 'company':
                $query->orderBy('company_name', $sortDirection);
                break;
            case 'amount':
                $query->orderBy('sponsorship_amount', $sortDirection);
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

        $sponsorships = $query->paginate($perPage);
        
        // Get statistics
        $stats = [
            'total' => CorporateSponsorship::count(),
            'verified' => CorporateSponsorship::where('is_verified', true)->count(),
            'pending' => CorporateSponsorship::where('status', 'pending')->count(),
            'active' => CorporateSponsorship::where('status', 'active')->count(),
            'suspended' => CorporateSponsorship::where('status', 'suspended')->count(),
            'expired' => CorporateSponsorship::where('sponsorship_end_date', '<', now())->count(),
            'total_amount' => CorporateSponsorship::sum('sponsorship_amount'),
            'average_amount' => CorporateSponsorship::avg('sponsorship_amount'),
        ];

        return view('admin.corporate.index', compact('sponsorships', 'stats'));
    }

    /**
     * Show the form for creating a new corporate sponsorship
     */
    public function create(): View
    {
        return view('admin.corporate.create');
    }

    /**
     * Store a newly created corporate sponsorship
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|string|in:startup,small_business,medium_business,large_corporation,non_profit,government,other',
            'company_address' => 'required|string|max:500',
            'company_city' => 'required|string|max:100',
            'company_state' => 'required|string|max:100',
            'company_country' => 'required|string|max:100',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:500',
            'company_size' => 'required|string|in:1-10,11-50,51-200,201-500,501-1000,1000+',
            'industry' => 'required|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_title' => 'required|string|max:255',
            'contact_person_email' => 'required|email|max:255',
            'contact_person_phone' => 'nullable|string|max:20',
            'sponsorship_type' => 'required|string|in:financial,product,service,media,event,educational,research,other',
            'sponsorship_amount' => 'required|numeric|min:0',
            'sponsorship_duration' => 'required|integer|min:1',
            'sponsorship_duration_unit' => 'required|string|in:days,weeks,months,years',
            'benefits_offered' => 'required|string|max:1000',
            'target_audience' => 'required|string|max:500',
            'marketing_materials' => 'nullable|string|max:1000',
            'status' => 'required|string|in:pending,active,suspended,expired',
            'verification_documents' => 'nullable|array',
            'verification_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'sponsorship_end_date' => 'nullable|date|after:now',
        ]);

        try {
            DB::beginTransaction();

            // Handle file uploads
            if ($request->hasFile('verification_documents')) {
                $documents = [];
                foreach ($request->file('verification_documents') as $file) {
                    $path = $file->store('corporate-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $sponsorship = CorporateSponsorship::create($validated);

            DB::commit();

            return redirect()->route('admin.corporate.index')
                           ->with('success', 'حمایت شرکتی با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating corporate sponsorship: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در ایجاد حمایت شرکتی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Display the specified corporate sponsorship
     */
    public function show(CorporateSponsorship $corporate): View
    {
        $corporate->load('user', 'sponsorshipBenefits');
        return view('admin.corporate.show', compact('corporate'));
    }

    /**
     * Show the form for editing the specified corporate sponsorship
     */
    public function edit(CorporateSponsorship $corporate): View
    {
        $corporate->load('user');
        return view('admin.corporate.edit', compact('corporate'));
    }

    /**
     * Update the specified corporate sponsorship
     */
    public function update(Request $request, CorporateSponsorship $corporate): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|string|in:startup,small_business,medium_business,large_corporation,non_profit,government,other',
            'company_address' => 'required|string|max:500',
            'company_city' => 'required|string|max:100',
            'company_state' => 'required|string|max:100',
            'company_country' => 'required|string|max:100',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:500',
            'company_size' => 'required|string|in:1-10,11-50,51-200,201-500,501-1000,1000+',
            'industry' => 'required|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_title' => 'required|string|max:255',
            'contact_person_email' => 'required|email|max:255',
            'contact_person_phone' => 'nullable|string|max:20',
            'sponsorship_type' => 'required|string|in:financial,product,service,media,event,educational,research,other',
            'sponsorship_amount' => 'required|numeric|min:0',
            'sponsorship_duration' => 'required|integer|min:1',
            'sponsorship_duration_unit' => 'required|string|in:days,weeks,months,years',
            'benefits_offered' => 'required|string|max:1000',
            'target_audience' => 'required|string|max:500',
            'marketing_materials' => 'nullable|string|max:1000',
            'status' => 'required|string|in:pending,active,suspended,expired',
            'verification_documents' => 'nullable|array',
            'verification_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'sponsorship_end_date' => 'nullable|date|after:now',
        ]);

        try {
            DB::beginTransaction();

            // Handle file uploads
            if ($request->hasFile('verification_documents')) {
                $documents = [];
                foreach ($request->file('verification_documents') as $file) {
                    $path = $file->store('corporate-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $corporate->update($validated);

            DB::commit();

            return redirect()->route('admin.corporate.index')
                           ->with('success', 'حمایت شرکتی با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating corporate sponsorship: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در به‌روزرسانی حمایت شرکتی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Remove the specified corporate sponsorship
     */
    public function destroy(CorporateSponsorship $corporate): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Delete verification documents
            if ($corporate->verification_documents) {
                foreach ($corporate->verification_documents as $document) {
                    Storage::disk('public')->delete($document);
                }
            }

            $corporate->delete();

            DB::commit();

            return redirect()->route('admin.corporate.index')
                           ->with('success', 'حمایت شرکتی با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting corporate sponsorship: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در حذف حمایت شرکتی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Verify a corporate sponsorship
     */
    public function verify(CorporateSponsorship $corporate): RedirectResponse
    {
        try {
            $corporate->update([
                'is_verified' => true,
                'verified_at' => now(),
                'status' => 'active'
            ]);

            return redirect()->back()
                           ->with('success', 'حمایت شرکتی با موفقیت تأیید شد.');

        } catch (\Exception $e) {
            Log::error('Error verifying corporate sponsorship: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تأیید حمایت شرکتی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Suspend a corporate sponsorship
     */
    public function suspend(CorporateSponsorship $corporate): RedirectResponse
    {
        try {
            $corporate->update(['status' => 'suspended']);

            return redirect()->back()
                           ->with('success', 'حمایت شرکتی با موفقیت معلق شد.');

        } catch (\Exception $e) {
            Log::error('Error suspending corporate sponsorship: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تعلیق حمایت شرکتی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Activate a corporate sponsorship
     */
    public function activate(CorporateSponsorship $corporate): RedirectResponse
    {
        try {
            $corporate->update(['status' => 'active']);

            return redirect()->back()
                           ->with('success', 'حمایت شرکتی با موفقیت فعال شد.');

        } catch (\Exception $e) {
            Log::error('Error activating corporate sponsorship: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در فعال‌سازی حمایت شرکتی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string|in:verify,suspend,activate,delete',
            'sponsorship_ids' => 'required|array|min:1',
            'sponsorship_ids.*' => 'exists:corporate_sponsorships,id'
        ]);

        try {
            DB::beginTransaction();

            $sponsorships = CorporateSponsorship::whereIn('id', $request->sponsorship_ids);

            switch ($request->action) {
                case 'verify':
                    $sponsorships->update([
                        'is_verified' => true,
                        'verified_at' => now(),
                        'status' => 'active'
                    ]);
                    $message = 'حمایت‌های شرکتی انتخاب شده با موفقیت تأیید شدند.';
                    break;

                case 'suspend':
                    $sponsorships->update(['status' => 'suspended']);
                    $message = 'حمایت‌های شرکتی انتخاب شده با موفقیت معلق شدند.';
                    break;

                case 'activate':
                    $sponsorships->update(['status' => 'active']);
                    $message = 'حمایت‌های شرکتی انتخاب شده با موفقیت فعال شدند.';
                    break;

                case 'delete':
                    // Delete verification documents
                    foreach ($sponsorships->get() as $sponsorship) {
                        if ($sponsorship->verification_documents) {
                            foreach ($sponsorship->verification_documents as $document) {
                                Storage::disk('public')->delete($document);
                            }
                        }
                    }
                    $sponsorships->delete();
                    $message = 'حمایت‌های شرکتی انتخاب شده با موفقیت حذف شدند.';
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
     * Export corporate sponsorships
     */
    public function export(Request $request)
    {
        // Implementation for exporting corporate sponsorships
        // This would typically generate a CSV or Excel file
        return response()->json(['message' => 'Export functionality will be implemented']);
    }

    /**
     * Get corporate sponsorship statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => CorporateSponsorship::count(),
            'verified' => CorporateSponsorship::where('is_verified', true)->count(),
            'pending' => CorporateSponsorship::where('status', 'pending')->count(),
            'active' => CorporateSponsorship::where('status', 'active')->count(),
            'suspended' => CorporateSponsorship::where('status', 'suspended')->count(),
            'expired' => CorporateSponsorship::where('sponsorship_end_date', '<', now())->count(),
            'total_amount' => CorporateSponsorship::sum('sponsorship_amount'),
            'average_amount' => CorporateSponsorship::avg('sponsorship_amount'),
            'by_company_type' => CorporateSponsorship::selectRaw('company_type, COUNT(*) as count')
                                                      ->groupBy('company_type')
                                                      ->get(),
            'by_sponsorship_type' => CorporateSponsorship::selectRaw('sponsorship_type, COUNT(*) as count')
                                                         ->groupBy('sponsorship_type')
                                                         ->get(),
            'by_status' => CorporateSponsorship::selectRaw('status, COUNT(*) as count')
                                               ->groupBy('status')
                                               ->get(),
        ];

        return response()->json($stats);
    }
}

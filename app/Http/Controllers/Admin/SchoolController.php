<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolPartnership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SchoolController extends Controller
{
    /**
     * Display a listing of school partnerships
     */
    public function index(Request $request): View
    {
        $query = SchoolPartnership::with('user');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('school_type')) {
            $query->where('school_type', $request->school_type);
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
            })->orWhere('school_name', 'like', '%' . $request->search . '%')
              ->orWhere('school_address', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch ($sortBy) {
            case 'name':
                $query->join('users', 'school_partnerships.user_id', '=', 'users.id')
                      ->orderBy('users.first_name', $sortDirection)
                      ->orderBy('users.last_name', $sortDirection);
                break;
            case 'school':
                $query->orderBy('school_name', $sortDirection);
                break;
            case 'students':
                $query->orderBy('student_count', $sortDirection);
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

        $schools = $query->paginate($perPage);
        
        // Get statistics
        $stats = [
            'total' => SchoolPartnership::count(),
            'verified' => SchoolPartnership::where('is_verified', true)->count(),
            'pending' => SchoolPartnership::where('status', 'pending')->count(),
            'active' => SchoolPartnership::where('status', 'active')->count(),
            'suspended' => SchoolPartnership::where('status', 'suspended')->count(),
            'expired' => SchoolPartnership::where('expires_at', '<', now())->count(),
            'total_students' => SchoolPartnership::sum('student_count'),
            'total_teachers' => SchoolPartnership::sum('teacher_count'),
        ];

        return view('admin.schools.index', compact('schools', 'stats'));
    }

    /**
     * Show the form for creating a new school partnership
     */
    public function create(): View
    {
        $users = User::where('role', 'parent')->get();
        return view('admin.schools.create', compact('users'));
    }

    /**
     * Store a newly created school partnership
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'school_name' => 'required|string|max:255',
            'school_type' => 'required|string|in:public_school,private_school,international_school,charter_school,homeschool_coop,other',
            'school_address' => 'required|string|max:500',
            'school_city' => 'required|string|max:100',
            'school_state' => 'required|string|max:100',
            'school_country' => 'required|string|max:100',
            'school_phone' => 'nullable|string|max:20',
            'school_email' => 'nullable|email|max:255',
            'school_website' => 'nullable|url|max:500',
            'principal_name' => 'required|string|max:255',
            'principal_email' => 'required|email|max:255',
            'principal_phone' => 'nullable|string|max:20',
            'student_count' => 'required|integer|min:0',
            'teacher_count' => 'required|integer|min:0',
            'grade_levels' => 'required|string|max:500',
            'curriculum_type' => 'required|string|max:255',
            'partnership_type' => 'required|string|in:full_access,limited_access,trial_access',
            'discount_rate' => 'required|numeric|min:0|max:100',
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
                    $path = $file->store('school-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $school = SchoolPartnership::create($validated);

            DB::commit();

            return redirect()->route('admin.schools.index')
                           ->with('success', 'مشارکت مدرسه با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating school partnership: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در ایجاد مشارکت مدرسه. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Display the specified school partnership
     */
    public function show(SchoolPartnership $school): View
    {
        $school->load('user', 'studentLicenses');
        return view('admin.schools.show', compact('school'));
    }

    /**
     * Show the form for editing the specified school partnership
     */
    public function edit(SchoolPartnership $school): View
    {
        $users = User::where('role', 'parent')->get();
        return view('admin.schools.edit', compact('school', 'users'));
    }

    /**
     * Update the specified school partnership
     */
    public function update(Request $request, SchoolPartnership $school): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'school_name' => 'required|string|max:255',
            'school_type' => 'required|string|in:public_school,private_school,international_school,charter_school,homeschool_coop,other',
            'school_address' => 'required|string|max:500',
            'school_city' => 'required|string|max:100',
            'school_state' => 'required|string|max:100',
            'school_country' => 'required|string|max:100',
            'school_phone' => 'nullable|string|max:20',
            'school_email' => 'nullable|email|max:255',
            'school_website' => 'nullable|url|max:500',
            'principal_name' => 'required|string|max:255',
            'principal_email' => 'required|email|max:255',
            'principal_phone' => 'nullable|string|max:20',
            'student_count' => 'required|integer|min:0',
            'teacher_count' => 'required|integer|min:0',
            'grade_levels' => 'required|string|max:500',
            'curriculum_type' => 'required|string|max:255',
            'partnership_type' => 'required|string|in:full_access,limited_access,trial_access',
            'discount_rate' => 'required|numeric|min:0|max:100',
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
                    $path = $file->store('school-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $school->update($validated);

            DB::commit();

            return redirect()->route('admin.schools.index')
                           ->with('success', 'مشارکت مدرسه با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating school partnership: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در به‌روزرسانی مشارکت مدرسه. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Remove the specified school partnership
     */
    public function destroy(SchoolPartnership $school): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Delete verification documents
            if ($school->verification_documents) {
                foreach ($school->verification_documents as $document) {
                    Storage::disk('public')->delete($document);
                }
            }

            $school->delete();

            DB::commit();

            return redirect()->route('admin.schools.index')
                           ->with('success', 'مشارکت مدرسه با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting school partnership: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در حذف مشارکت مدرسه. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Verify a school partnership
     */
    public function verify(SchoolPartnership $school): RedirectResponse
    {
        try {
            $school->update([
                'is_verified' => true,
                'verified_at' => now(),
                'status' => 'active'
            ]);

            return redirect()->back()
                           ->with('success', 'مشارکت مدرسه با موفقیت تأیید شد.');

        } catch (\Exception $e) {
            Log::error('Error verifying school partnership: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تأیید مشارکت مدرسه. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Suspend a school partnership
     */
    public function suspend(SchoolPartnership $school): RedirectResponse
    {
        try {
            $school->update(['status' => 'suspended']);

            return redirect()->back()
                           ->with('success', 'مشارکت مدرسه با موفقیت معلق شد.');

        } catch (\Exception $e) {
            Log::error('Error suspending school partnership: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تعلیق مشارکت مدرسه. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Activate a school partnership
     */
    public function activate(SchoolPartnership $school): RedirectResponse
    {
        try {
            $school->update(['status' => 'active']);

            return redirect()->back()
                           ->with('success', 'مشارکت مدرسه با موفقیت فعال شد.');

        } catch (\Exception $e) {
            Log::error('Error activating school partnership: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در فعال‌سازی مشارکت مدرسه. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string|in:verify,suspend,activate,delete',
            'school_ids' => 'required|array|min:1',
            'school_ids.*' => 'exists:school_partnerships,id'
        ]);

        try {
            DB::beginTransaction();

            $schools = SchoolPartnership::whereIn('id', $request->school_ids);

            switch ($request->action) {
                case 'verify':
                    $schools->update([
                        'is_verified' => true,
                        'verified_at' => now(),
                        'status' => 'active'
                    ]);
                    $message = 'مشارکت‌های مدرسه انتخاب شده با موفقیت تأیید شدند.';
                    break;

                case 'suspend':
                    $schools->update(['status' => 'suspended']);
                    $message = 'مشارکت‌های مدرسه انتخاب شده با موفقیت معلق شدند.';
                    break;

                case 'activate':
                    $schools->update(['status' => 'active']);
                    $message = 'مشارکت‌های مدرسه انتخاب شده با موفقیت فعال شدند.';
                    break;

                case 'delete':
                    // Delete verification documents
                    foreach ($schools->get() as $school) {
                        if ($school->verification_documents) {
                            foreach ($school->verification_documents as $document) {
                                Storage::disk('public')->delete($document);
                            }
                        }
                    }
                    $schools->delete();
                    $message = 'مشارکت‌های مدرسه انتخاب شده با موفقیت حذف شدند.';
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
     * Export school partnerships
     */
    public function export(Request $request)
    {
        // Implementation for exporting school partnerships
        // This would typically generate a CSV or Excel file
        return response()->json(['message' => 'Export functionality will be implemented']);
    }

    /**
     * Get school partnership statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => SchoolPartnership::count(),
            'verified' => SchoolPartnership::where('is_verified', true)->count(),
            'pending' => SchoolPartnership::where('status', 'pending')->count(),
            'active' => SchoolPartnership::where('status', 'active')->count(),
            'suspended' => SchoolPartnership::where('status', 'suspended')->count(),
            'expired' => SchoolPartnership::where('expires_at', '<', now())->count(),
            'total_students' => SchoolPartnership::sum('student_count'),
            'total_teachers' => SchoolPartnership::sum('teacher_count'),
            'by_school_type' => SchoolPartnership::selectRaw('school_type, COUNT(*) as count')
                                               ->groupBy('school_type')
                                               ->get(),
            'by_status' => SchoolPartnership::selectRaw('status, COUNT(*) as count')
                                            ->groupBy('status')
                                            ->get(),
        ];

        return response()->json($stats);
    }
}

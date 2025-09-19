<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    /**
     * Display a listing of teacher accounts
     */
    public function index(Request $request): View
    {
        $query = TeacherAccount::with('user');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('institution_type')) {
            $query->where('institution_type', $request->institution_type);
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
            })->orWhere('institution_name', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch ($sortBy) {
            case 'name':
                $query->join('users', 'teacher_accounts.user_id', '=', 'users.id')
                      ->orderBy('users.first_name', $sortDirection)
                      ->orderBy('users.last_name', $sortDirection);
                break;
            case 'institution':
                $query->orderBy('institution_name', $sortDirection);
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

        $teachers = $query->paginate($perPage);
        
        // Get statistics
        $stats = [
            'total' => TeacherAccount::count(),
            'verified' => TeacherAccount::where('is_verified', true)->count(),
            'pending' => TeacherAccount::where('status', 'pending')->count(),
            'active' => TeacherAccount::where('status', 'active')->count(),
            'suspended' => TeacherAccount::where('status', 'suspended')->count(),
            'expired' => TeacherAccount::where('expires_at', '<', now())->count(),
            'total_students' => TeacherAccount::sum('student_count'),
            'total_licenses' => TeacherAccount::sum('max_student_licenses'),
        ];

        return view('admin.teachers.index', compact('teachers', 'stats'));
    }

    /**
     * Show the form for creating a new teacher account
     */
    public function create(): View
    {
        $users = User::where('role', 'parent')->get();
        return view('admin.teachers.create', compact('users'));
    }

    /**
     * Store a newly created teacher account
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'institution_name' => 'required|string|max:255',
            'institution_type' => 'required|string|in:public_school,private_school,university,college,homeschool,other',
            'teaching_subject' => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'certification_number' => 'nullable|string|max:255',
            'certification_authority' => 'nullable|string|max:255',
            'certification_date' => 'nullable|date',
            'student_count' => 'required|integer|min:0',
            'max_student_licenses' => 'required|integer|min:1',
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
                    $path = $file->store('teacher-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $teacher = TeacherAccount::create($validated);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                           ->with('success', 'حساب معلم با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating teacher account: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در ایجاد حساب معلم. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Display the specified teacher account
     */
    public function show(TeacherAccount $teacher): View
    {
        $teacher->load('user', 'studentLicenses');
        return view('admin.teachers.show', compact('teacher'));
    }

    /**
     * Show the form for editing the specified teacher account
     */
    public function edit(TeacherAccount $teacher): View
    {
        $users = User::where('role', 'parent')->get();
        return view('admin.teachers.edit', compact('teacher', 'users'));
    }

    /**
     * Update the specified teacher account
     */
    public function update(Request $request, TeacherAccount $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'institution_name' => 'required|string|max:255',
            'institution_type' => 'required|string|in:public_school,private_school,university,college,homeschool,other',
            'teaching_subject' => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'certification_number' => 'nullable|string|max:255',
            'certification_authority' => 'nullable|string|max:255',
            'certification_date' => 'nullable|date',
            'student_count' => 'required|integer|min:0',
            'max_student_licenses' => 'required|integer|min:1',
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
                    $path = $file->store('teacher-documents', 'public');
                    $documents[] = $path;
                }
                $validated['verification_documents'] = $documents;
            }

            $teacher->update($validated);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                           ->with('success', 'حساب معلم با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating teacher account: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در به‌روزرسانی حساب معلم. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Remove the specified teacher account
     */
    public function destroy(TeacherAccount $teacher): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Delete verification documents
            if ($teacher->verification_documents) {
                foreach ($teacher->verification_documents as $document) {
                    Storage::disk('public')->delete($document);
                }
            }

            $teacher->delete();

            DB::commit();

            return redirect()->route('admin.teachers.index')
                           ->with('success', 'حساب معلم با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting teacher account: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در حذف حساب معلم. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Verify a teacher account
     */
    public function verify(TeacherAccount $teacher): RedirectResponse
    {
        try {
            $teacher->update([
                'is_verified' => true,
                'verified_at' => now(),
                'status' => 'active'
            ]);

            return redirect()->back()
                           ->with('success', 'حساب معلم با موفقیت تأیید شد.');

        } catch (\Exception $e) {
            Log::error('Error verifying teacher account: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تأیید حساب معلم. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Suspend a teacher account
     */
    public function suspend(TeacherAccount $teacher): RedirectResponse
    {
        try {
            $teacher->update(['status' => 'suspended']);

            return redirect()->back()
                           ->with('success', 'حساب معلم با موفقیت معلق شد.');

        } catch (\Exception $e) {
            Log::error('Error suspending teacher account: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تعلیق حساب معلم. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Activate a teacher account
     */
    public function activate(TeacherAccount $teacher): RedirectResponse
    {
        try {
            $teacher->update(['status' => 'active']);

            return redirect()->back()
                           ->with('success', 'حساب معلم با موفقیت فعال شد.');

        } catch (\Exception $e) {
            Log::error('Error activating teacher account: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در فعال‌سازی حساب معلم. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string|in:verify,suspend,activate,delete',
            'teacher_ids' => 'required|array|min:1',
            'teacher_ids.*' => 'exists:teacher_accounts,id'
        ]);

        try {
            DB::beginTransaction();

            $teachers = TeacherAccount::whereIn('id', $request->teacher_ids);

            switch ($request->action) {
                case 'verify':
                    $teachers->update([
                        'is_verified' => true,
                        'verified_at' => now(),
                        'status' => 'active'
                    ]);
                    $message = 'حساب‌های معلم انتخاب شده با موفقیت تأیید شدند.';
                    break;

                case 'suspend':
                    $teachers->update(['status' => 'suspended']);
                    $message = 'حساب‌های معلم انتخاب شده با موفقیت معلق شدند.';
                    break;

                case 'activate':
                    $teachers->update(['status' => 'active']);
                    $message = 'حساب‌های معلم انتخاب شده با موفقیت فعال شدند.';
                    break;

                case 'delete':
                    // Delete verification documents
                    foreach ($teachers->get() as $teacher) {
                        if ($teacher->verification_documents) {
                            foreach ($teacher->verification_documents as $document) {
                                Storage::disk('public')->delete($document);
                            }
                        }
                    }
                    $teachers->delete();
                    $message = 'حساب‌های معلم انتخاب شده با موفقیت حذف شدند.';
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
     * Export teacher accounts
     */
    public function export(Request $request)
    {
        // Implementation for exporting teacher accounts
        // This would typically generate a CSV or Excel file
        return response()->json(['message' => 'Export functionality will be implemented']);
    }

    /**
     * Get teacher statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => TeacherAccount::count(),
            'verified' => TeacherAccount::where('is_verified', true)->count(),
            'pending' => TeacherAccount::where('status', 'pending')->count(),
            'active' => TeacherAccount::where('status', 'active')->count(),
            'suspended' => TeacherAccount::where('status', 'suspended')->count(),
            'expired' => TeacherAccount::where('expires_at', '<', now())->count(),
            'total_students' => TeacherAccount::sum('student_count'),
            'total_licenses' => TeacherAccount::sum('max_student_licenses'),
            'by_institution_type' => TeacherAccount::selectRaw('institution_type, COUNT(*) as count')
                                                      ->groupBy('institution_type')
                                                      ->get(),
            'by_status' => TeacherAccount::selectRaw('status, COUNT(*) as count')
                                         ->groupBy('status')
                                         ->get(),
        ];

        return response()->json($stats);
    }
}

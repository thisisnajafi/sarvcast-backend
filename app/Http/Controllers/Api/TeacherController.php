<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeacherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    protected $teacherService;

    public function __construct(TeacherService $teacherService)
    {
        $this->teacherService = $teacherService;
    }

    /**
     * Create teacher account
     */
    public function createTeacherAccount(Request $request): JsonResponse
    {
        $request->validate([
            'institution_name' => 'required|string|max:255',
            'institution_type' => 'required|string|in:school,university,private_center,cultural_center,online_platform',
            'teaching_subject' => 'required|string|in:persian_language,literature,history,culture,arts,general_education,special_needs',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'certification_number' => 'nullable|string|max:100',
            'certification_authority' => 'nullable|string|max:255',
            'certification_date' => 'nullable|date',
            'max_student_licenses' => 'nullable|integer|min:1|max:500',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'verification_documents' => 'nullable|array',
        ]);

        $userId = Auth::id();
        $result = $this->teacherService->createTeacherAccount($userId, $request->all());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get teacher account
     */
    public function getTeacherAccount(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->teacherService->getTeacherAccount($userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Create student license
     */
    public function createStudentLicense(Request $request): JsonResponse
    {
        $request->validate([
            'teacher_account_id' => 'required|integer|exists:teacher_accounts,id',
            'student_user_id' => 'required|integer|exists:users,id',
            'license_type' => 'nullable|string|in:individual,bulk',
            'original_price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $result = $this->teacherService->createStudentLicense(
            $request->teacher_account_id,
            $request->student_user_id,
            $request->all()
        );
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get teacher's student licenses
     */
    public function getTeacherStudentLicenses(Request $request, int $teacherAccountId): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->teacherService->getTeacherStudentLicenses($teacherAccountId, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get student's licenses
     */
    public function getStudentLicenses(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->teacherService->getStudentLicenses($userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get program benefits
     */
    public function getProgramBenefits(): JsonResponse
    {
        $result = $this->teacherService->getProgramBenefits();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get institution types
     */
    public function getInstitutionTypes(): JsonResponse
    {
        $result = $this->teacherService->getInstitutionTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get teaching subjects
     */
    public function getTeachingSubjects(): JsonResponse
    {
        $result = $this->teacherService->getTeachingSubjects();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Verify teacher account (Admin only)
     */
    public function verifyTeacherAccount(int $teacherAccountId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->teacherService->verifyTeacherAccount($teacherAccountId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global teacher statistics (Admin only)
     */
    public function getGlobalStatistics(): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->teacherService->getGlobalStatistics();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Process expired licenses (Admin only)
     */
    public function processExpiredLicenses(): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->teacherService->processExpiredLicenses();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}

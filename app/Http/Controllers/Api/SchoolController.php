<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SchoolService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SchoolController extends Controller
{
    protected $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->schoolService = $schoolService;
    }

    /**
     * Create school partnership
     */
    public function createPartnership(Request $request): JsonResponse
    {
        $request->validate([
            'partner_id' => 'required|integer|exists:affiliate_partners,id',
            'school_name' => 'required|string|max:255',
            'school_type' => 'required|string|in:public,private,international,cultural_center,language_institute',
            'school_level' => 'required|string|in:elementary,middle,high,university,mixed',
            'location' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'student_count' => 'required|integer|min:1',
            'teacher_count' => 'required|integer|min:1',
            'partnership_model' => 'required|string|in:revenue_sharing,licensing,pilot',
            'partnership_start_date' => 'required|date',
            'partnership_end_date' => 'required|date|after:partnership_start_date',
            'verification_documents' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $result = $this->schoolService->createPartnership($request->partner_id, $request->all());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Create school license
     */
    public function createLicense(Request $request): JsonResponse
    {
        $request->validate([
            'partnership_id' => 'required|integer|exists:school_partnerships,id',
            'user_id' => 'required|integer|exists:users,id',
            'license_type' => 'required|string|in:student,teacher,admin',
            'user_role' => 'required|string|in:student,teacher,administrator,coordinator',
            'original_price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $result = $this->schoolService->createLicense(
            $request->partnership_id,
            $request->user_id,
            $request->all()
        );
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Verify school partnership (Admin only)
     */
    public function verifyPartnership(int $partnershipId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->schoolService->verifyPartnership($partnershipId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get partnership details
     */
    public function getPartnership(int $partnershipId): JsonResponse
    {
        $result = $this->schoolService->getPartnership($partnershipId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get partnership licenses
     */
    public function getPartnershipLicenses(Request $request, int $partnershipId): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->schoolService->getPartnershipLicenses($partnershipId, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get user's school licenses
     */
    public function getUserLicenses(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->schoolService->getUserLicenses($userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get partnership models
     */
    public function getPartnershipModels(): JsonResponse
    {
        $result = $this->schoolService->getPartnershipModels();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get school types
     */
    public function getSchoolTypes(): JsonResponse
    {
        $result = $this->schoolService->getSchoolTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get school levels
     */
    public function getSchoolLevels(): JsonResponse
    {
        $result = $this->schoolService->getSchoolLevels();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get partnership benefits
     */
    public function getPartnershipBenefits(): JsonResponse
    {
        $result = $this->schoolService->getPartnershipBenefits();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global school statistics (Admin only)
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

        $result = $this->schoolService->getGlobalStatistics();
        
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

        $result = $this->schoolService->processExpiredLicenses();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}

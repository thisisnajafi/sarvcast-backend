<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserSearchController extends Controller
{
    /**
     * Search users for teacher assignment
     */
    public function searchUsers(Request $request): JsonResponse
    {
        // Log the incoming request
        \Log::info('🔍 UserSearchController.searchUsers - Request received', [
            'query' => $request->input('query'),
            'limit' => $request->input('limit'),
            'exclude_teachers' => $request->input('exclude_teachers'),
            'all_params' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:100',
            'limit' => 'sometimes|integer|min:1|max:50',
            'exclude_teachers' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            \Log::warning('❌ UserSearchController.searchUsers - Validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $query = $request->input('query');
        $limit = $request->input('limit', 20);
        $excludeTeachers = $request->boolean('exclude_teachers', false);

        $usersQuery = User::query();

        // Exclude users who are already teachers if requested
        if ($excludeTeachers) {
            $usersQuery->whereDoesntHave('teacherAccount');
        }

        // Search by name, or phone
        $usersQuery->where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('phone_number', 'like', "%{$query}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
        });

        // Only include verified users (users with verified phone)
        $usersQuery->whereNotNull('phone_verified_at');

        $users = $usersQuery->select([
            'id',
            'first_name',
            'last_name',
            'phone_number',
            'profile_image_url',
            'created_at'
        ])
        ->with(['teacherAccount' => function ($query) {
            $query->select(['id', 'user_id', 'institution_name', 'status', 'is_verified']);
        }])
        ->limit($limit)
        ->get();

        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->first_name . ' ' . $user->last_name, // Keep for backward compatibility
                'phone_number' => $user->phone_number,
                'phone' => $user->phone_number, // Keep for backward compatibility
                'profile_image_url' => $user->profile_image_url,
                'avatar' => $user->profile_image_url, // Keep for backward compatibility
                'role' => $user->role ?? 'basic', // Include role field
                'is_teacher' => $user->teacherAccount ? true : false,
                'teacher_status' => $user->teacherAccount ? $user->teacherAccount->status : null,
                'institution_name' => $user->teacherAccount ? $user->teacherAccount->institution_name : null,
                'created_at' => $user->created_at->format('Y/m/d'),
            ];
        });

        $response = [
            'success' => true,
            'data' => [
                'users' => $formattedUsers,
                'total' => $formattedUsers->count(),
                'query' => $query,
            ],
        ];

        // Log the response
        \Log::info('✅ UserSearchController.searchUsers - Response sent', [
            'query' => $query,
            'users_found' => $formattedUsers->count(),
            'response' => $response,
        ]);

        return response()->json($response);
    }

    /**
     * Get user details for assignment
     */
    public function getUserDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه کاربر نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $userId = $request->input('user_id');

        $user = User::with(['teacherAccount'])
            ->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر یافت نشد',
            ], 404);
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'is_verified' => $user->is_verified,
            'created_at' => $user->created_at->format('Y/m/d'),
            'teacher_account' => null,
        ];

        if ($user->teacherAccount) {
            $userData['teacher_account'] = [
                'id' => $user->teacherAccount->id,
                'institution_name' => $user->teacherAccount->institution_name,
                'institution_type' => $user->teacherAccount->institution_type,
                'teaching_subject' => $user->teacherAccount->teaching_subject,
                'status' => $user->teacherAccount->status,
                'is_verified' => $user->teacherAccount->is_verified,
                'discount_rate' => $user->teacherAccount->discount_rate,
                'commission_rate' => $user->teacherAccount->commission_rate,
                'coupon_code' => $user->teacherAccount->coupon_code,
                'created_at' => $user->teacherAccount->created_at->format('Y/m/d'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $userData,
        ]);
    }

    /**
     * Get available teachers for school assignment
     */
    public function getAvailableTeachers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'sometimes|string|max:100',
            'institution_type' => 'sometimes|string',
            'teaching_subject' => 'sometimes|string',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 400);
        }

        $query = $request->input('query');
        $institutionType = $request->input('institution_type');
        $teachingSubject = $request->input('teaching_subject');
        $limit = $request->input('limit', 20);

        $teachersQuery = User::whereHas('teacherAccount', function ($q) {
            $q->where('status', 'verified')
              ->where('is_verified', true);
        });

        // Apply search filters
        if ($query) {
            $teachersQuery->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('phone_number', 'like', "%{$query}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            });
        }

        $teachers = $teachersQuery->with(['teacherAccount' => function ($query) use ($institutionType, $teachingSubject) {
            $query->select([
                'id', 'user_id', 'institution_name', 'institution_type',
                'teaching_subject', 'years_of_experience', 'discount_rate',
                'commission_rate', 'coupon_code', 'status', 'is_verified'
            ]);

            if ($institutionType) {
                $query->where('institution_type', $institutionType);
            }

            if ($teachingSubject) {
                $query->where('teaching_subject', $teachingSubject);
            }
        }])
        ->select(['id', 'first_name', 'last_name', 'phone_number', 'profile_image_url'])
        ->limit($limit)
        ->get();

        $formattedTeachers = $teachers->map(function ($user) {
            $teacherAccount = $user->teacherAccount;

            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone_number,
                'avatar' => $user->profile_image_url,
                'teacher_account' => [
                    'id' => $teacherAccount->id,
                    'institution_name' => $teacherAccount->institution_name,
                    'institution_type' => $teacherAccount->institution_type,
                    'teaching_subject' => $teacherAccount->teaching_subject,
                    'years_of_experience' => $teacherAccount->years_of_experience,
                    'discount_rate' => $teacherAccount->discount_rate,
                    'commission_rate' => $teacherAccount->commission_rate,
                    'coupon_code' => $teacherAccount->coupon_code,
                    'status' => $teacherAccount->status,
                    'is_verified' => $teacherAccount->is_verified,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'teachers' => $formattedTeachers,
                'total' => $formattedTeachers->count(),
                'filters' => [
                    'query' => $query,
                    'institution_type' => $institutionType,
                    'teaching_subject' => $teachingSubject,
                ],
            ],
        ]);
    }
}

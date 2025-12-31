<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PersonController extends Controller
{
    /**
     * Display a listing of people
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Person::query();

            // Search functionality
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('bio', 'like', "%{$searchTerm}%");
                });
            }

            // Filter by role
            if ($request->has('role') && $request->role) {
                $query->whereJsonContains('roles', $request->role);
            }

            // Filter by verification status
            if ($request->has('verified') && $request->verified !== null) {
                $query->where('is_verified', $request->verified);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            $allowedSortFields = ['name', 'total_stories', 'total_episodes', 'average_rating', 'created_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $people = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'people' => $people->items(),
                    'pagination' => [
                        'current_page' => $people->currentPage(),
                        'last_page' => $people->lastPage(),
                        'per_page' => $people->perPage(),
                        'total' => $people->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching people list', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست افراد'
            ], 500);
        }
    }

    /**
     * Store a newly created person
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'bio' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'roles' => 'required|array|min:1',
                'roles.*' => 'string|in:voice_actor,director,producer,author,narrator',
                'is_verified' => 'boolean'
            ], [
                'name.required' => 'نام الزامی است',
                'name.max' => 'نام نمی‌تواند بیش از 100 کاراکتر باشد',
                'bio.max' => 'بیوگرافی نمی‌تواند بیش از 1000 کاراکتر باشد',
                'image.image' => 'فایل باید تصویر باشد',
                'image.mimes' => 'فرمت تصویر باید jpeg، png، jpg یا webp باشد',
                'image.max' => 'حجم تصویر نمی‌تواند بیش از 2 مگابایت باشد',
                'roles.required' => 'حداقل یک نقش الزامی است',
                'roles.array' => 'نقش‌ها باید آرایه باشند',
                'roles.min' => 'حداقل یک نقش الزامی است',
                'roles.*.in' => 'نقش نامعتبر است'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌ها',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personData = $request->only(['name', 'bio', 'roles', 'is_verified']);
            $personData['is_verified'] = $request->boolean('is_verified', false);

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images/people'), $imageName);
                // Store only the relative path
                $personData['image_url'] = 'people/' . $imageName;
            }

            $person = Person::create($personData);

            Log::info('Person created successfully', [
                'person_id' => $person->id,
                'name' => $person->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'فرد با موفقیت ایجاد شد',
                'data' => $person
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating person', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد فرد'
            ], 500);
        }
    }

    /**
     * Display the specified person
     */
    public function show(Person $person): JsonResponse
    {
        try {
            $person->load(['stories', 'episodes']);

            return response()->json([
                'success' => true,
                'data' => $person
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching person details', [
                'error' => $e->getMessage(),
                'person_id' => $person->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات فرد'
            ], 500);
        }
    }

    /**
     * Update the specified person
     */
    public function update(Request $request, Person $person): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:100',
                'bio' => 'nullable|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'roles' => 'sometimes|required|array|min:1',
                'roles.*' => 'string|in:voice_actor,director,producer,author,narrator',
                'is_verified' => 'boolean'
            ], [
                'name.required' => 'نام الزامی است',
                'name.max' => 'نام نمی‌تواند بیش از 100 کاراکتر باشد',
                'bio.max' => 'بیوگرافی نمی‌تواند بیش از 1000 کاراکتر باشد',
                'image.image' => 'فایل باید تصویر باشد',
                'image.mimes' => 'فرمت تصویر باید jpeg، png، jpg یا webp باشد',
                'image.max' => 'حجم تصویر نمی‌تواند بیش از 2 مگابایت باشد',
                'roles.required' => 'حداقل یک نقش الزامی است',
                'roles.array' => 'نقش‌ها باید آرایه باشند',
                'roles.min' => 'حداقل یک نقش الزامی است',
                'roles.*.in' => 'نقش نامعتبر است'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی داده‌ها',
                    'errors' => $validator->errors()
                ], 422);
            }

            $personData = $request->only(['name', 'bio', 'roles', 'is_verified']);

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($person->attributes['image_url'] && file_exists(public_path('images/' . $person->attributes['image_url']))) {
                    unlink(public_path('images/' . $person->attributes['image_url']));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images/people'), $imageName);
                // Store only the relative path
                $personData['image_url'] = 'people/' . $imageName;
            }

            $person->update($personData);

            Log::info('Person updated successfully', [
                'person_id' => $person->id,
                'name' => $person->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'فرد با موفقیت به‌روزرسانی شد',
                'data' => $person
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating person', [
                'error' => $e->getMessage(),
                'person_id' => $person->id,
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی فرد'
            ], 500);
        }
    }

    /**
     * Remove the specified person
     */
    public function destroy(Person $person): JsonResponse
    {
        try {
            // Check if person has associated stories or episodes
            if ($person->stories()->count() > 0 || $person->episodes()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'نمی‌توان فردی که داستان یا قسمت دارد را حذف کرد'
                ], 422);
            }

            // Delete image if exists
            if ($person->image_url) {
                $imagePath = str_replace('/storage/', '', $person->image_url);
                Storage::disk('public')->delete($imagePath);
            }

            $person->delete();

            Log::info('Person deleted successfully', [
                'person_id' => $person->id,
                'name' => $person->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'فرد با موفقیت حذف شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting person', [
                'error' => $e->getMessage(),
                'person_id' => $person->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف فرد'
            ], 500);
        }
    }

    /**
     * Get person statistics
     */
    public function statistics(Person $person): JsonResponse
    {
        try {
            $stats = [
                'total_stories' => $person->stories()->count(),
                'total_episodes' => $person->episodes()->count(),
                'average_rating' => $person->average_rating,
                'total_play_count' => $person->stories()->sum('play_count'),
                'roles' => $person->roles,
                'is_verified' => $person->is_verified,
                'created_at' => $person->created_at,
                'last_active_at' => $person->last_active_at
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching person statistics', [
                'error' => $e->getMessage(),
                'person_id' => $person->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار فرد'
            ], 500);
        }
    }

    /**
     * Get people by role
     */
    public function getByRole(Request $request, string $role): JsonResponse
    {
        try {
            $validRoles = ['voice_actor', 'director', 'producer', 'author', 'narrator'];
            
            if (!in_array($role, $validRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'نقش نامعتبر است'
                ], 422);
            }

            $people = Person::whereJsonContains('roles', $role)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $people
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching people by role', [
                'error' => $e->getMessage(),
                'role' => $role
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت افراد بر اساس نقش'
            ], 500);
        }
    }

    /**
     * Get stories by a specific person
     */
    public function stories(Person $person, Request $request): JsonResponse
    {
        try {
            $query = $person->stories()->with(['category', 'narrator', 'author', 'director', 'people'])
                ->published();

            // Apply pagination
            $perPage = min($request->get('per_page', 20), 100);
            $stories = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Stories retrieved successfully',
                'data' => [
                    'stories' => $stories->items(),
                    'pagination' => [
                        'current_page' => $stories->currentPage(),
                        'last_page' => $stories->lastPage(),
                        'per_page' => $stories->perPage(),
                        'total' => $stories->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching person stories', [
                'error' => $e->getMessage(),
                'person_id' => $person->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت داستان‌های فرد'
            ], 500);
        }
    }
}

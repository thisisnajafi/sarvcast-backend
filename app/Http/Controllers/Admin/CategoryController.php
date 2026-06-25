<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::query()
            ->withCount([
                'stories as published_stories_count' => function ($q) {
                    $q->where('status', 'published');
                },
                'stories as total_stories_count'
            ]);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'story_count', 'published_stories_count', 'total_episodes', 'average_rating', 'sort_order', 'created_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $categories = $query->paginate($perPage);
        
        // Get statistics
        $stats = [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'total_stories' => Category::sum('story_count'),
            'total_episodes' => Category::sum('total_episodes'),
            'avg_rating' => Category::avg('average_rating'),
        ];

        return view('admin.categories.index', compact('categories', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:categories',
            'slug' => 'nullable|string|max:100|unique:categories',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'color' => 'nullable|string|max:7',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except(['image']);

           // Handle image upload
           if ($request->hasFile('image')) {
               $image = $request->file('image');
               $imageName = time() . '_' . $image->getClientOriginalName();
               $imagePath = public_path('images/categories/' . $imageName);
               $image->move(public_path('images/categories'), $imageName);
               // Store only the relative path
               $data['icon_path'] = 'categories/' . $imageName;
           }

        $category = Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category->load(['stories' => function($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'slug' => 'nullable|string|max:100|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'color' => 'nullable|string|max:7',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except(['image']);

           // Handle image upload
           if ($request->hasFile('image')) {
               // Delete old image if exists
               if ($category->icon_path && file_exists(public_path('images/' . $category->icon_path))) {
                   unlink(public_path('images/' . $category->icon_path));
               }
               
               $image = $request->file('image');
               $imageName = time() . '_' . $image->getClientOriginalName();
               $image->move(public_path('images/categories'), $imageName);
               // Store only the relative path
               $data['icon_path'] = 'categories/' . $imageName;
           }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'دسته‌بندی با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        // Check if category has stories
        if ($category->stories()->count() > 0) {
            return redirect()->back()
                ->with('error', 'نمی‌توان دسته‌بندی‌ای که دارای داستان است را حذف کرد.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'دسته‌بندی با موفقیت حذف شد.');
    }

    // API Methods for Next dashboard
    public function apiIndex(Request $request)
    {
        $query = $this->buildCategoryApiListQuery($request);
        $query->withCount([
            'stories as published_stories_count' => function ($q) {
                $q->where('status', 'published');
            },
            'stories as total_stories_count',
        ]);
        $this->applyCategoryListSort($query, $request);

        $perPage = $this->resolveCategoryListPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildCategoryApiListQuery($request);
        $query->withCount([
            'stories as published_stories_count' => function ($q) {
                $q->where('status', 'published');
            },
            'stories as total_stories_count',
        ]);
        $this->applyCategoryListSort($query, $request);

        $filename = 'categories-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'name', 'slug', 'is_active', 'sort_order', 'story_count', 'total_episodes', 'average_rating', 'created_at']);

            $query->clone()->select([
                'id', 'name', 'slug', 'is_active', 'sort_order', 'story_count', 'total_episodes', 'average_rating', 'created_at',
            ])->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->slug,
                        $row->is_active ? '1' : '0',
                        $row->sort_order,
                        $row->story_count,
                        $row->total_episodes,
                        $row->average_rating,
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'slug' => 'nullable|string|max:100|unique:categories,slug',
            'description' => 'nullable|string|max:500',
            'icon_path' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = Category::create($validated);

        return AdminApiResponse::success($category, 'Category created successfully', 201);
    }

    public function apiShow(Category $category)
    {
        $data = $category->loadCount([
            'stories as published_stories_count' => function ($q) {
                $q->where('status', 'published');
            },
            'stories as total_stories_count',
        ]);

        return AdminApiResponse::success($data);
    }

    public function apiUpdate(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:categories,name,' . $category->id,
            'slug' => 'nullable|string|max:100|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string|max:500',
            'icon_path' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
            'is_active' => 'sometimes|required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category->update($validated);

        return AdminApiResponse::success($category->fresh(), 'Category updated successfully');
    }

    public function apiDestroy(Category $category)
    {
        if ($category->stories()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing stories',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $category->delete();

        return AdminApiResponse::okMessage('Category deleted successfully');
    }

    public function apiBulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $ids = $validated['category_ids'] ?? $validated['selected_items'] ?? [];
        if (count($ids) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ دسته‌بندی‌ای انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($ids as $id) {
            $category = Category::find($id);
            if (! $category) {
                $failureCount++;
                continue;
            }

            if ($validated['action'] === 'delete') {
                if ($category->stories()->count() > 0) {
                    $failureCount++;
                    continue;
                }
                $category->delete();
                $successCount++;
            } elseif ($validated['action'] === 'activate') {
                $category->update(['is_active' => true]);
                $successCount++;
            } else {
                $category->update(['is_active' => false]);
                $successCount++;
            }
        }

        $message = "عملیات {$validated['action']} روی {$successCount} مورد انجام شد";
        if ($failureCount > 0) {
            $message .= "؛ {$failureCount} مورد ناموفق بود";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ]);
    }

    public function apiStatistics()
    {
        return AdminApiResponse::success([
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'total_stories' => Category::sum('story_count'),
            'total_episodes' => Category::sum('total_episodes'),
            'avg_rating' => Category::avg('average_rating'),
        ]);
    }

    private function buildCategoryApiListQuery(Request $request)
    {
        $query = Category::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        if ($request->filled('is_active')) {
            $val = $request->input('is_active');
            $query->where('is_active', filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $val);
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', Carbon::now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', Carbon::now()->subMonth());
                    break;
                case 'year':
                    $query->where('created_at', '>=', Carbon::now()->subYear());
                    break;
            }
        }

        return $query;
    }

    private function applyCategoryListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', $request->input('sort_by', 'sort_order'));

        $sortDir = 'asc';
        if ($request->has('sortDir')) {
            $sortDir = strtolower((string) $request->input('sortDir')) === 'desc' ? 'desc' : 'asc';
        } else {
            $legacyOrder = $request->input('sort_order');
            if (is_string($legacyOrder) && in_array(strtolower($legacyOrder), ['asc', 'desc'], true)) {
                $sortDir = strtolower($legacyOrder) === 'desc' ? 'desc' : 'asc';
            }
        }

        $allowed = ['name', 'story_count', 'published_stories_count', 'total_episodes', 'average_rating', 'sort_order', 'created_at', 'id'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'sort_order';
            $sortDir = 'asc';
        }
        $query->orderBy($sortBy, $sortDir);
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }
    }

    private function resolveCategoryListPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 15));

        return min(100, max(1, $raw ?: 15));
    }
}

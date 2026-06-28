<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Person;
use App\Services\PersonStoryContributionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PersonController extends Controller
{
    /**
     * Display a listing of people
     */
    public function index(Request $request)
    {
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

        $people = $query->paginate(20);

        return view('admin.people.index', compact('people'));
    }

    /**
     * Show the form for creating a new person
     */
    public function create()
    {
        $roles = ['voice_actor', 'director', 'producer', 'author', 'narrator'];
        return view('admin.people.create', compact('roles'));
    }

    /**
     * Store a newly created person
     */
    public function store(Request $request)
    {
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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
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

        Person::create($personData);

        return redirect()->route('admin.people.index')
            ->with('success', 'فرد با موفقیت ایجاد شد');
    }

    /**
     * Display the specified person
     */
    public function show(Person $person)
    {
        $person->load(['stories', 'episodes']);
        return view('admin.people.show', compact('person'));
    }

    /**
     * Show the form for editing the specified person
     */
    public function edit(Person $person)
    {
        $roles = ['voice_actor', 'director', 'producer', 'author', 'narrator'];
        return view('admin.people.edit', compact('person', 'roles'));
    }

    /**
     * Update the specified person
     */
    public function update(Request $request, Person $person)
    {
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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $personData = $request->only(['name', 'bio', 'roles', 'is_verified']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($person->image_url && file_exists(public_path('images/' . $person->image_url))) {
                unlink(public_path('images/' . $person->image_url));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images/people'), $imageName);
            // Store only the relative path
            $personData['image_url'] = 'people/' . $imageName;
        }

        $person->update($personData);

        return redirect()->route('admin.people.index')
            ->with('success', 'فرد با موفقیت به‌روزرسانی شد');
    }

    /**
     * Remove the specified person
     */
    public function destroy(Person $person)
    {
        // Check if person has associated stories or episodes
        if ($person->stories()->count() > 0 || $person->episodes()->count() > 0) {
            return redirect()->back()
                ->with('error', 'نمی‌توان فردی که داستان یا قسمت دارد را حذف کرد');
        }

        // Delete image if exists
        if ($person->image_url) {
            $imagePath = str_replace('/storage/', '', $person->image_url);
            Storage::disk('public')->delete($imagePath);
        }

        $person->delete();

        return redirect()->route('admin.people.index')
            ->with('success', 'فرد با موفقیت حذف شد');
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = $this->buildPersonApiListQuery($request);
        $this->applyPersonListSort($query, $request);

        $perPage = $this->resolvePersonPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $people = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($people);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildPersonApiListQuery($request);
        $this->applyPersonListSort($query, $request);

        $filename = 'people-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'name', 'roles', 'is_verified', 'total_stories', 'total_episodes', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        implode('|', $row->roles ?? []),
                        $row->is_verified ? '1' : '0',
                        $row->total_stories,
                        $row->total_episodes,
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
            'name' => 'required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|in:voice_actor,director,producer,author,narrator',
            'is_verified' => 'boolean',
            'image_url' => 'nullable|string|max:255',
        ]);

        $validated['is_verified'] = $request->boolean('is_verified', false);
        $person = Person::create($validated);

        return AdminApiResponse::success($person, 'فرد با موفقیت ایجاد شد', 201);
    }

    public function apiShow(Person $person)
    {
        $person->load([
            'stories',
            'episodes.story',
            'episodeVoiceActors.episode.story',
        ]);

        $contributions = app(PersonStoryContributionService::class)->summarizeForPerson($person);

        return AdminApiResponse::success(array_merge($person->toArray(), [
            'story_contributions' => $contributions,
        ]));
    }

    public function apiUpdate(Request $request, Person $person)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'roles' => 'sometimes|required|array|min:1',
            'roles.*' => 'string|in:voice_actor,director,producer,author,narrator',
            'is_verified' => 'boolean',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($request->has('is_verified')) {
            $validated['is_verified'] = $request->boolean('is_verified');
        }

        $person->update($validated);

        return AdminApiResponse::success($person->fresh(), 'فرد با موفقیت به‌روزرسانی شد');
    }

    public function apiDestroy(Person $person)
    {
        if ($person->stories()->count() > 0 || $person->episodes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توان فردی که داستان یا قسمت دارد را حذف کرد',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $person->delete();

        return AdminApiResponse::okMessage('فرد با موفقیت حذف شد');
    }

    public function apiBulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:verify,unverify,delete',
            'person_ids' => 'nullable|array',
            'person_ids.*' => 'integer|exists:people,id',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:people,id',
        ]);

        $ids = $validated['person_ids'] ?? $validated['selected_items'] ?? [];
        if ($ids === []) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ موردی انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        if ($validated['action'] === 'verify') {
            Person::whereIn('id', $ids)->update(['is_verified' => true]);
        } elseif ($validated['action'] === 'unverify') {
            Person::whereIn('id', $ids)->update(['is_verified' => false]);
        } else {
            Person::whereIn('id', $ids)->delete();
        }

        return AdminApiResponse::okMessage('عملیات با موفقیت انجام شد');
    }

    public function apiStatistics()
    {
        $stats = [
            'total_people' => Person::count(),
            'verified_people' => Person::where('is_verified', true)->count(),
            'voice_actors' => Person::whereJsonContains('roles', 'voice_actor')->count(),
            'authors' => Person::whereJsonContains('roles', 'author')->count(),
            'directors' => Person::whereJsonContains('roles', 'director')->count(),
        ];

        return AdminApiResponse::success($stats);
    }

    private function buildPersonApiListQuery(Request $request)
    {
        $query = Person::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('bio', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('role')) {
            $query->whereJsonContains('roles', $request->role);
        }

        if ($request->filled('verified')) {
            $query->where('is_verified', $request->boolean('verified'));
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
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

    private function applyPersonListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', $request->input('sort_by', 'created_at'));
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'name', 'total_stories', 'total_episodes', 'average_rating', 'is_verified'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolvePersonPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}
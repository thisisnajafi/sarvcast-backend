<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
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
        $roles = ['voice_actor', 'director', 'writer', 'producer', 'author', 'narrator'];
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
            'roles.*' => 'string|in:voice_actor,director,writer,producer,author,narrator',
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
        $roles = ['voice_actor', 'director', 'writer', 'producer', 'author', 'narrator'];
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
            'roles.*' => 'string|in:voice_actor,director,writer,producer,author,narrator',
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
}
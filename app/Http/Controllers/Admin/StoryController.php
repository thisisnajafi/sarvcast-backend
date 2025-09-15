<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Category;
use App\Models\Person;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Story::with(['category', 'director', 'narrator']);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $stories = $query->latest()->paginate(20);
        $categories = Category::where('is_active', true)->get();

        return view('admin.stories.index', compact('stories', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $people = Person::all();
        
        return view('admin.stories.create', compact('categories', 'people'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'age_group' => 'required|string',
            'language' => 'required|string',
            'duration' => 'required|integer|min:1',
            'director_id' => 'nullable|exists:people,id',
            'writer_id' => 'nullable|exists:people,id',
            'author_id' => 'nullable|exists:people,id',
            'narrator_id' => 'nullable|exists:people,id',
            'is_premium' => 'boolean',
            'is_completely_free' => 'boolean',
            'status' => 'required|in:draft,pending,approved,rejected,published',
            'image_url' => 'required|url',
            'cover_image_url' => 'nullable|url',
            'tags' => 'nullable|array',
        ]);

        $story = Story::create($validated);

        return redirect()->route('admin.stories.index')
            ->with('success', 'داستان با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Story $story)
    {
        $story->load(['category', 'director', 'writer', 'author', 'narrator', 'episodes', 'people']);
        
        return view('admin.stories.show', compact('story'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Story $story)
    {
        $categories = Category::where('is_active', true)->get();
        $people = Person::all();
        
        return view('admin.stories.edit', compact('story', 'categories', 'people'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Story $story)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'age_group' => 'required|string',
            'language' => 'required|string',
            'duration' => 'required|integer|min:1',
            'director_id' => 'nullable|exists:people,id',
            'writer_id' => 'nullable|exists:people,id',
            'author_id' => 'nullable|exists:people,id',
            'narrator_id' => 'nullable|exists:people,id',
            'is_premium' => 'boolean',
            'is_completely_free' => 'boolean',
            'status' => 'required|in:draft,pending,approved,rejected,published',
            'image_url' => 'required|url',
            'cover_image_url' => 'nullable|url',
            'tags' => 'nullable|array',
        ]);

        $story->update($validated);

        return redirect()->route('admin.stories.index')
            ->with('success', 'داستان با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Story $story)
    {
        $story->delete();

        return redirect()->route('admin.stories.index')
            ->with('success', 'داستان با موفقیت حذف شد.');
    }
}

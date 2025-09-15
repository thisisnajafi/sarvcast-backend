<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Story;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EpisodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Episode::with(['story', 'narrator']);

        // Apply filters
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $episodes = $query->latest()->paginate(20);

        return view('admin.episodes.index', compact('episodes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stories = Story::published()->get();
        $narrators = Person::where('type', 'narrator')->get();

        return view('admin.episodes.create', compact('stories', 'narrators'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'audio_file' => 'required|file|mimes:mp3,wav,m4a|max:102400', // 100MB max
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'narrator_id' => 'nullable|exists:people,id',
            'is_premium' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'release_date' => 'nullable|date',
        ]);

        $data = $request->except(['audio_file', 'cover_image']);

        // Handle audio file upload
        if ($request->hasFile('audio_file')) {
            $audioFile = $request->file('audio_file');
            $audioPath = $audioFile->store('episodes/audio', 'public');
            $data['audio_url'] = Storage::url($audioPath);
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $coverImage = $request->file('cover_image');
            $imagePath = $coverImage->store('episodes/covers', 'public');
            $data['cover_image_url'] = Storage::url($imagePath);
        }

        Episode::create($data);

        return redirect()->route('admin.episodes.index')
            ->with('success', 'اپیزود با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Episode $episode)
    {
        $episode->load(['story', 'narrator', 'people']);

        return view('admin.episodes.show', compact('episode'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Episode $episode)
    {
        $stories = Story::published()->get();
        $narrators = Person::where('type', 'narrator')->get();

        return view('admin.episodes.edit', compact('episode', 'stories', 'narrators'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Episode $episode)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'episode_number' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a|max:102400',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'narrator_id' => 'nullable|exists:people,id',
            'is_premium' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'release_date' => 'nullable|date',
        ]);

        $data = $request->except(['audio_file', 'cover_image']);

        // Handle audio file upload
        if ($request->hasFile('audio_file')) {
            // Delete old audio file
            if ($episode->audio_url) {
                $oldPath = str_replace('/storage/', '', $episode->audio_url);
                Storage::disk('public')->delete($oldPath);
            }

            $audioFile = $request->file('audio_file');
            $audioPath = $audioFile->store('episodes/audio', 'public');
            $data['audio_url'] = Storage::url($audioPath);
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover image
            if ($episode->cover_image_url) {
                $oldPath = str_replace('/storage/', '', $episode->cover_image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $coverImage = $request->file('cover_image');
            $imagePath = $coverImage->store('episodes/covers', 'public');
            $data['cover_image_url'] = Storage::url($imagePath);
        }

        $episode->update($data);

        return redirect()->route('admin.episodes.index')
            ->with('success', 'اپیزود با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Episode $episode)
    {
        // Delete associated files
        if ($episode->audio_url) {
            $audioPath = str_replace('/storage/', '', $episode->audio_url);
            Storage::disk('public')->delete($audioPath);
        }

        if ($episode->cover_image_url) {
            $imagePath = str_replace('/storage/', '', $episode->cover_image_url);
            Storage::disk('public')->delete($imagePath);
        }

        $episode->delete();

        return redirect()->route('admin.episodes.index')
            ->with('success', 'اپیزود با موفقیت حذف شد.');
    }
}

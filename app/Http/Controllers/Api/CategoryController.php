<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all active categories
     */
    public function index()
    {
        $categories = Category::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories
            ]
        ]);
    }

    /**
     * Get stories in a category
     */
    public function stories(Category $category, Request $request)
    {
        $query = $category->stories()->published();

        // Apply filters
        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        // Apply sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->orderBy('play_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            default:
                $query->latest();
        }

        $stories = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'stories' => $stories->items(),
                'pagination' => [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total()
                ]
            ]
        ]);
    }
}

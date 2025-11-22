<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    /**
     * Display a listing of published news
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $categoryId = $request->get('category_id');
        $isPremium = $request->get('is_premium');
        
        $query = News::with(['category', 'user', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($isPremium !== null) {
            $query->where('is_premium', filter_var($isPremium, FILTER_VALIDATE_BOOLEAN));
        }

        $news = $query->paginate($perPage);

        return response()->json($news);
    }

    /**
     * Display all news (for admin)
     */
    public function all(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        
        $news = News::with(['category', 'user', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($news);
    }

    /**
     * Search news
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $perPage = $request->get('per_page', 10);

        if (empty($query)) {
            return response()->json([
                'data' => [],
                'message' => 'Search query is required'
            ], 400);
        }

        $news = News::with(['category', 'user', 'tags'])
            ->published()
            ->search($query)
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return response()->json($news);
    }

    /**
     * Display the specified news
     */
    public function show(string $id): JsonResponse
    {
        $news = News::with(['category', 'user', 'tags'])->findOrFail($id);

        return response()->json($news);
    }

    /**
     * Store a newly created news
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:news,slug',
            'content' => 'required|string',
            'thumbnail' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'published_at' => 'nullable|date',
            'is_premium' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $news = News::create($validated);

        if (!empty($tags)) {
            $news->tags()->attach($tags);
        }

        $news->load(['category', 'user', 'tags']);

        return response()->json($news, 201);
    }

    /**
     * Update the specified news
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $news = News::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:news,slug,' . $id,
            'content' => 'sometimes|required|string',
            'thumbnail' => 'nullable|string|max:500',
            'category_id' => 'sometimes|required|exists:categories,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'published_at' => 'nullable|date',
            'is_premium' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $tags = $validated['tags'] ?? null;
        unset($validated['tags']);

        $news->update($validated);

        if ($tags !== null) {
            $news->tags()->sync($tags);
        }

        $news->load(['category', 'user', 'tags']);

        return response()->json($news);
    }

    /**
     * Remove the specified news
     */
    public function destroy(string $id): JsonResponse
    {
        $news = News::findOrFail($id);
        $news->delete();

        return response()->json([
            'message' => 'News deleted successfully'
        ]);
    }
}


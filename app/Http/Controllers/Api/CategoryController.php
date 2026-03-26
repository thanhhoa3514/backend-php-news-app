<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
class CategoryController extends Controller
{
    use AuthorizesApiRequests;

    /**
     * Display a listing of categories
     */
    public function index(): JsonResponse
    {
        $categories = \Illuminate\Support\Facades\Cache::remember('categories.all', 3600, function () {
            return Category::withCount('news')
                ->orderBy('name')
                ->get();
        });

        return response()->json($categories);
    }

    /**
     * Display the specified category
     */
    public function show(string $slug): JsonResponse
    {
        Log::info('Category slug: ' . $slug);
        
        $category = \Illuminate\Support\Facades\Cache::remember('category.detail.' . $slug, 3600, function () use ($slug) {
            return Category::where('slug', $slug)
                ->withCount('news')
                ->firstOrFail();
        });

        return response()->json($category);
    }

    /**
     * Get news for a specific category
     */
    public function news(Request $request, string $slug): JsonResponse
    {
        Log::info('Category slug: ' . $slug);
        $perPage = $request->get('per_page', 10);
        
        $category = Category::where('slug', $slug)->firstOrFail();
        
        $news = $category->news()
            ->with(['category', 'user', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return response()->json($news);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureEditorOrAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
        ]);

        // Generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = Category::create($validated);
        Log::info('Category created: ' . $category->id);
        return response()->json($category, 201);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureEditorOrAdmin();
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $id,
        ]);

        // Update slug if name changed but slug not provided
        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        Log::info('Category updated: ' . $category->id);
        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified category
     */
    public function destroy(string $id): JsonResponse
    {
        $this->ensureEditorOrAdmin();
        $category = Category::findOrFail($id);
        
        // Check if category has news
        if ($category->news()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing news'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}

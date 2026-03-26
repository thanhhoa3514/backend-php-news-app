<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TagController extends Controller
{
    use AuthorizesApiRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        Log::info($request->all());
        $perPage = $request->get('per_page', 10);
        $search = $request->get('q');
        $all = $request->get('all'); // If true, return all tags without pagination

        $query = Tag::withCount([
            'news as news_count' => function ($newsQuery) {
                $newsQuery->published()->free();
            }
        ]);

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        if ($all) {
            $tags = $query->get();
            return response()->json($tags);
        }

        $tags = $query->paginate($perPage);

        return response()->json($tags);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureEditorOrAdmin();
        Log::info($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7', // Hex color e.g. #FF0000
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        Log::info($validated);
        $tag = Tag::create($validated);
        Log::info($tag);
        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $tag = Tag::withCount([
            'news as news_count' => function ($newsQuery) {
                $newsQuery->published()->free();
            }
        ])
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        return response()->json($tag);
    }

    /**
     * Get news by tag (by id or slug)
     */
    public function news(Request $request, string $idOrSlug): JsonResponse
    {
        $perPage = $request->get('per_page', 10);

        // Find tag by id or slug
        $tag = Tag::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        $news = $tag->news()
            ->with(['category', 'user', 'tags'])
            ->published()
            ->free()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'tag' => $tag,
            'news' => $news
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureEditorOrAdmin();
        Log::info($request->all());
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:tags,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        Log::info($validated);
        $tag->update($validated);

        return response()->json($tag);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->ensureEditorOrAdmin();
        $tag = Tag::findOrFail($id);
        
        // Detach from all news first (although cascade delete on foreign key might handle this, explicit is safe)
        $tag->news()->detach();
        
        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
    /**
     * Display all news (for admin)
     */
    public function all(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $categoryId = $request->get('category_id');
        $status = $request->get('status');
        $search = $request->get('q');
        
        $query = News::with(['category', 'user', 'tags'])
            ->orderBy('created_at', 'desc');

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->search($search);
        }

        if ($status && $status !== 'all') {
            switch ($status) {
                case 'published':
                    $query->where('published_at', '<=', now());
                    break;
                case 'draft':
                    $query->whereNull('published_at');
                    break;
                case 'pending':
                    $query->where('published_at', '>', now());
                    break;
            }
        }

        $news = $query->paginate($perPage);

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

    /**
     * Generate AI news using Gemini API
     */
    public function generateAi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'count' => 'required|integer|min:1|max:5',
            'language' => 'required|string',
            'tone' => 'required|string',
            'length' => 'required|string',
            'prompt' => 'nullable|string',
        ]);

        Log::info('Gemini API Request: ' . json_encode($validated));
        try {
            $geminiService = app(\App\Services\GeminiService::class);
            
            $articles = $geminiService->generateArticles($validated);
            Log::info('Gemini API Articles: ' . json_encode($articles));

            // Save to Database
            $generation = \App\Models\AiGeneration::create([
                'user_id' => auth()->id(),
                'category' => $validated['category'],
                'prompt' => $validated['prompt'] ?? null,
                'generated_content' => $articles,
                'status' => 'draft'
            ]);

            return response()->json([
                'data' => $articles,
                'generation_id' => $generation->id,
                'message' => 'Articles generated and saved to history successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            return response()->json([
                'data' => [],
                'message' => 'Failed to generate articles: ' . $e->getMessage()
            ], 500);
        }
    }
}


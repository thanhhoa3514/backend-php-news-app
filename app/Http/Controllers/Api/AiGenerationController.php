<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AiGenerationController extends Controller
{
    /**
     * Get AI generation history for the current user
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        
        $generations = AiGeneration::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($generations);
    }

    /**
     * Store a new AI generation (Internal use mostly)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'prompt' => 'nullable|string',
            'generated_content' => 'required|array',
            'status' => 'in:draft,saved'
        ]);

        $generation = AiGeneration::create([
            'user_id' => Auth::id(),
            'category' => $validated['category'],
            'prompt' => $validated['prompt'] ?? null,
            'generated_content' => $validated['generated_content'],
            'status' => $validated['status'] ?? 'draft'
        ]);

        return response()->json($generation, 201);
    }

    /**
     * Mark generation as saved (published)
     */
    public function markAsSaved(string $id): JsonResponse
    {
        $generation = AiGeneration::where('user_id', Auth::id())->findOrFail($id);
        
        $generation->update(['status' => 'saved']);

        return response()->json([
            'message' => 'Marked as saved',
            'data' => $generation
        ]);
    }

    /**
     * Get specific generation
     */
    public function show(string $id): JsonResponse
    {
        $generation = AiGeneration::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($generation);
    }
}

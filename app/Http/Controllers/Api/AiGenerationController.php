<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Models\AiGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiGenerationController extends Controller
{
    use AuthorizesApiRequests;

    /**
     * Get AI generation history for the current user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->ensureEditorOrAdmin();
        $perPage = $request->get('per_page', 10);
        
        $query = AiGeneration::query();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $generations = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($generations);
    }

    /**
     * Store a new AI generation (Internal use mostly)
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureEditorOrAdmin();
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
        $user = $this->ensureEditorOrAdmin();
        $generation = AiGeneration::findOrFail($id);
        $this->ensureOwnerOrAdmin($generation->user_id, $user, 'You can only modify your own AI generations.');
        
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
        $user = $this->ensureEditorOrAdmin();
        $generation = AiGeneration::findOrFail($id);
        $this->ensureOwnerOrAdmin($generation->user_id, $user, 'You can only view your own AI generations.');
        return response()->json($generation);
    }
}

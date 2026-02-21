<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AiGeneration;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class GenerateAiNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $generationId;
    public $validatedData;

    /**
     * Create a new job instance.
     */
    public function __construct($generationId, $validatedData)
    {
        $this->generationId = $generationId;
        $this->validatedData = $validatedData;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService): void
    {
        $generation = AiGeneration::find($this->generationId);
        if (!$generation) return;

        try {
            Log::info('Background Gemini API Request for generation: ' . $this->generationId);
            $articles = $geminiService->generateArticles($this->validatedData);
            
            $generation->update([
                'generated_content' => $articles,
                'status' => 'draft'
            ]);
            Log::info('Background Gemini API completed for generation: ' . $this->generationId);
        } catch (\Exception $e) {
            Log::error('Background Gemini API Error: ' . $e->getMessage());
            $generation->update([
                'status' => 'failed'
            ]);
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
private string $baseUrl = 'https://generativelanguage.googleapis.com/v1/models/';
    private array $models = [
        'gemini-2.5-flash',
        'gemini-3-pro-preview'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Generate news articles using Gemini API
     */
    public function generateArticles(array $params): array
    {
        $prompt = $this->buildPrompt($params);
        $lastException = null;
        foreach ($this->models as $model) {
            try {
          
                
                // Gọi hàm callApi với model cụ thể
                $response = $this->callGeminiApi($prompt, $model);
                
                // Nếu thành công -> Log và trả về ngay
                Log::info("Gemini Service: Thành công với model [{$model}]");
                return $this->parseResponse($response, $params);

            } catch (\Exception $e) {
                $lastException = $e;
                
                // Lấy mã lỗi (404, 429...)
                $statusCode = $e->getCode();
                // Nếu lỗi -> Log cảnh báo và tiếp tục vòng lặp sang model kế tiếp
                Log::warning("Gemini Service: Model [{$model}] thất bại. Lỗi: " . $e->getMessage());
                if ($statusCode == 429) {
                    sleep(2); 
                }
                
                continue;
            }
        }
        // Nếu thử hết danh sách mà vẫn lỗi -> Ném lỗi cuối cùng ra
        Log::error('Gemini Service: Tất cả các model đều thất bại.');
        throw new \Exception('Failed to generate articles after trying all models. Last error: ' . $lastException->getMessage());
    }

    /**
     * Build prompt from user parameters
     */
    private function buildPrompt(array $params): string
    {
        $count = $params['count'] ?? 1;
        $category = $params['category'] ?? 'general';
        $language = $params['language'] ?? 'vietnamese';
        $tone = $params['tone'] ?? 'neutral';
        $length = $params['length'] ?? 'medium';
        $customPrompt = $params['prompt'] ?? '';

        $lengthGuide = match($length) {
            'short' => 'approx. 1000 words',
            'medium' => 'approx. 2000-2500 words',
            'long' => 'detailed, in-depth article over 3000 words',
            default => 'approx. 2000 words'
        };

        $structureGuide = match($length) {
            'short' => 'Include an introduction, 2-3 body paragraphs, and a conclusion.',
            'medium' => 'Include a catchy introduction, 4-5 detailed sections with headings, and a strong conclusion.',
            'long' => 'Include a comprehensive introduction, multiple in-depth sections with h2 and h3 headings, bullet points, analysis, and a thought-provoking conclusion.',
            default => 'Standard article structure with introduction, body, and conclusion.'
        };

        $prompt = <<<PROMPT
You are a senior professional news editor and writer. Generate {$count} unique, high-quality, and realistic news articles about "{$category}".

Requirements:
- Language: {$language}
- Tone: {$tone}
- Target Length: {$lengthGuide}
- Structure: {$structureGuide}
- Format: HTML (use <p>, <h2>, <h3>, <ul>, <li>, <strong>, <em>). Do NOT use <h1>, <html>, <head>, or <body> tags.
- Content: Must be original, engaging, and relevant to current trends. Avoid generic fillers.
- Images: Suggest 2-3 specific, relevant keywords for finding a stock image.

{$customPrompt}

IMPORTANT: Return ONLY a valid JSON array. Do not include markdown formatting (like ```json).
Each article object must have these exact fields:
- title (string): A catchy, SEO-friendly headline.
- summary (string): A compelling 2-3 sentence summary/excerpt.
- content (string): The full HTML article content. Ensure it meets the length requirement of {$lengthGuide}.
- image_keyword (string): English keywords for image search (e.g., "business meeting office", "future technology ai").

Example format:
[
  {
    "title": "The Future of AI in Healthcare",
    "summary": "How artificial intelligence is revolutionizing patient care and diagnosis.",
    "content": "<p>Introduction...</p><h2>Key Benefits</h2><p>Details...</p>...",
    "image_keyword": "doctor using tablet hospital"
  }
]

Generate {$count} articles now:
PROMPT;

        return $prompt;
    }

    /**
     * Call Gemini API
     */
    private function callGeminiApi(string $prompt, string $model): string
    {
        $url = $this->baseUrl . $model . ':generateContent';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey // Bảo mật hơn
        ])
        ->timeout(60)
        ->post($url, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 8192,
                // 'response_mime_type' => 'application/json', // Ép kiểu JSON xịn
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()}: " . $response->body());
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * Parse and format Gemini response
     */
    private function parseResponse(string $response, array $params): array
    {
        // Extract JSON from response (Gemini sometimes adds markdown code blocks)
        $response = trim($response);
        $response = preg_replace('/^```json\s*/', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);
        
        $articles = json_decode($response, true);

        if (!is_array($articles)) {
            throw new \Exception('Failed to parse JSON response from Gemini');
        }

        // Format and validate articles
        $formattedArticles = [];
        foreach ($articles as $index => $article) {
            if (!isset($article['title']) || !isset($article['content'])) {
                continue; // Skip invalid articles
            }
             $keyword = $article['image_keyword'] ?? 'news';
            $formattedArticles[] = [
                'id' => uniqid(),
                'title' => $article['title'],
                'category' => $params['category'],
                'summary' => $article['summary'] ?? '',
                'content' => $article['content'],
      'thumbnail' => "[https://loremflickr.com/640/480/](https://loremflickr.com/640/480/){$keyword}",
                'date' => now()->toDateString(),
                'category_id' => $params['category_id'] ?? null,
                'author' => 'Gemini AI',
                'is_premium' => rand(0, 1) == 1,
                'is_generated' => true
            ];
        }

        return $formattedArticles;
    }
}

<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\News;
use App\Services\ImageService;
use Illuminate\Support\Facades\Log;

class UploadCloudinaryImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $newsId;
    public $tempThumbnailUrl;

    /**
     * Create a new job instance.
     */
    public function __construct($newsId, $tempThumbnailUrl)
    {
        $this->newsId = $newsId;
        $this->tempThumbnailUrl = $tempThumbnailUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(ImageService $imageService): void
    {
        $news = News::find($this->newsId);
        if (!$news) return;

        try {
            $permanentUrl = $imageService->uploadFromUrl(
                $this->tempThumbnailUrl,
                'ai_generated'
            );
            $news->update(['thumbnail' => $permanentUrl]);
            Log::info('Background Cloudinary upload completed for news: ' . $this->newsId);
        } catch (\Exception $e) {
            Log::error('Background Cloudinary upload failed: ' . $e->getMessage());
        }
    }
}

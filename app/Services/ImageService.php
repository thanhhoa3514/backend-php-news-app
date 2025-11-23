<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * Upload file from user upload
     * 
     * @param UploadedFile $file
     * @param string $folder
     * @return string Secure URL of uploaded image
     */
    public function uploadFromFile(UploadedFile $file, string $folder = 'news'): string
    {
        try {
            Log::info('Uploading file: ' . $file->getClientOriginalName());
            
            $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder' => $folder,
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                ],
            ]);

            return $result['secure_url'];
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed: ' . $e->getMessage());
            throw new \Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Upload image from URL (for AI-generated images)
     * 
     * @param string $url
     * @param string $folder
     * @return string Secure URL of uploaded image
     */
    public function uploadFromUrl(string $url, string $folder = 'ai_generated'): string
    {
        try {
            Log::info('Uploading URL: ' . $url);
            
            $result = $this->cloudinary->uploadApi()->upload($url, [
                'folder' => $folder,
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                ],
            ]);

            return $result['secure_url'];
        } catch (\Exception $e) {
            Log::error('Cloudinary URL upload failed: ' . $e->getMessage());
            throw new \Exception('Failed to upload image from URL');
        }
    }

    /**
     * Delete image from Cloudinary
     * 
     * @param string $publicId
     * @return bool
     */
    public function delete(string $publicId): bool
    {
        try {
            Log::info('Deleting public ID: ' . $publicId);
            $this->cloudinary->uploadApi()->destroy($publicId);
            return true;
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract public ID from Cloudinary URL
     * 
     * @param string $url
     * @return string|null
     */
    public function extractPublicId(string $url): ?string
    {
        // Extract public ID from URL like:
        // https://res.cloudinary.com/CLOUD_NAME/image/upload/v123456/folder/image.jpg
        if (preg_match('/\/v\d+\/(.+)\.\w+$/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

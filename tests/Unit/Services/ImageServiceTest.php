<?php

namespace Tests\Unit\Services;

use App\Services\ImageService;
use PHPUnit\Framework\TestCase;

class ImageServiceTest extends TestCase
{
    private function makeServiceWithoutConstructor(): ImageService
    {
        return (new \ReflectionClass(ImageService::class))->newInstanceWithoutConstructor();
    }

    public function test_extract_public_id_returns_folder_and_filename_without_extension(): void
    {
        $service = $this->makeServiceWithoutConstructor();

        $url = 'https://res.cloudinary.com/demo/image/upload/v123456/news/cover-image.jpg';
        $publicId = $service->extractPublicId($url);

        $this->assertSame('news/cover-image', $publicId);
    }

    public function test_extract_public_id_returns_null_for_invalid_url(): void
    {
        $service = $this->makeServiceWithoutConstructor();

        $url = 'https://example.com/not-cloudinary.jpg';
        $publicId = $service->extractPublicId($url);

        $this->assertNull($publicId);
    }
}


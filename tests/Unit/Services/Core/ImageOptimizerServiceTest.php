<?php

namespace Tests\Unit\Services\Core;

use App\Services\Core\ImageOptimizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ImageOptimizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->service = new ImageOptimizerService;
    }

    public function test_it_optimizes_and_stores_image_as_webp(): void
    {
        $file = UploadedFile::fake()->image('test_product.jpg', 800, 600);

        $path = $this->service->optimizeAndStore($file, 'products', 'product', 'public');

        $this->assertNotNull($path);
        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $content = Storage::disk('public')->get($path);
        $this->assertNotEmpty($content);

        // Verify WebP format magic bytes (RIFF .... WEBP)
        $this->assertStringStartsWith('RIFF', $content);
        $this->assertStringContainsString('WEBP', substr($content, 0, 16));
    }

    public function test_it_resizes_large_images_exceeding_profile_bounds(): void
    {
        // 2400x1600 exceeds product bounds (1200x1200)
        $file = UploadedFile::fake()->image('huge_photo.jpg', 2400, 1600);

        $path = $this->service->optimizeAndStore($file, 'products', 'product', 'public');

        Storage::disk('public')->assertExists($path);
        $content = Storage::disk('public')->get($path);
        $image = @imagecreatefromstring($content);

        $this->assertNotFalse($image);
        $this->assertLessThanOrEqual(1200, imagesx($image));
        $this->assertLessThanOrEqual(1200, imagesy($image));
        // Ratio should be preserved: 1200x800
        $this->assertSame(1200, imagesx($image));
        $this->assertSame(800, imagesy($image));

        imagedestroy($image);
    }

    public function test_it_does_not_upscale_small_images(): void
    {
        // 200x150 is smaller than avatar bounds (400x400)
        $file = UploadedFile::fake()->image('small_avatar.png', 200, 150);

        $path = $this->service->optimizeAndStore($file, 'user/photo', 'avatar', 'public');

        Storage::disk('public')->assertExists($path);
        $content = Storage::disk('public')->get($path);
        $image = @imagecreatefromstring($content);

        $this->assertNotFalse($image);
        $this->assertSame(200, imagesx($image));
        $this->assertSame(150, imagesy($image));

        imagedestroy($image);
    }

    public function test_it_handles_png_with_transparency(): void
    {
        $file = UploadedFile::fake()->image('logo_transparent.png', 500, 500);

        $path = $this->service->optimizeAndStore($file, 'business/image', 'logo', 'public');

        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.webp', $path);
    }

    public function test_it_deletes_stored_image(): void
    {
        $file = UploadedFile::fake()->image('to_delete.jpg', 300, 300);
        $path = $this->service->optimizeAndStore($file, 'products', 'product', 'public');

        Storage::disk('public')->assertExists($path);

        $result = $this->service->delete($path, 'public');
        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_it_handles_delete_with_full_storage_url(): void
    {
        $file = UploadedFile::fake()->image('to_delete_url.jpg', 300, 300);
        $path = $this->service->optimizeAndStore($file, 'products', 'product', 'public');

        $fullUrl = '/storage/'.$path;
        $result = $this->service->delete($fullUrl, 'public');

        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_it_returns_false_when_deleting_null_or_nonexistent_path(): void
    {
        $this->assertFalse($this->service->delete(null));
        $this->assertFalse($this->service->delete('nonexistent/file.webp', 'public'));
    }
}

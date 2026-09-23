<?php

namespace App\Services\Core;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImageOptimizerService
{
    /**
     * Profile configurations with max dimensions and WebP quality factors.
     *
     * @var array<string, array{max_width: int, max_height: int, quality: int}>
     */
    protected array $profiles = [
        'product' => [
            'max_width' => 1200,
            'max_height' => 1200,
            'quality' => 82,
        ],
        'logo' => [
            'max_width' => 800,
            'max_height' => 800,
            'quality' => 85,
        ],
        'avatar' => [
            'max_width' => 400,
            'max_height' => 400,
            'quality' => 82,
        ],
        'payment_proof' => [
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 85,
        ],
        'default' => [
            'max_width' => 1200,
            'max_height' => 1200,
            'quality' => 80,
        ],
    ];

    /**
     * Optimize an uploaded image and store it to the designated storage disk.
     *
     * @return string Relative file path on the storage disk.
     */
    public function optimizeAndStore(
        UploadedFile $file,
        string $directory,
        string $profile = 'default',
        ?string $disk = null
    ): string {
        $targetDisk = $this->resolveDisk($disk);
        $directory = trim($directory, '/');

        // Gracefully fallback if GD extension is not available
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return $file->store($directory, $targetDisk);
        }

        try {
            $realPath = $file->getRealPath();
            if (! $realPath || ! file_exists($realPath)) {
                return $file->store($directory, $targetDisk);
            }

            $content = file_get_contents($realPath);
            if ($content === false) {
                return $file->store($directory, $targetDisk);
            }

            // Create GD image resource from binary string
            $image = @imagecreatefromstring($content);
            if ($image === false) {
                return $file->store($directory, $targetDisk);
            }

            // Auto-rotate image if EXIF orientation tag is present (for JPEG/TIFF)
            $image = $this->autoRotateExif($image, $realPath);

            // Get profile configuration
            $config = $this->profiles[$profile] ?? $this->profiles['default'];

            // Resize image proportionally if larger than maximum bounding box
            $image = $this->resizeProportionally(
                $image,
                $config['max_width'],
                $config['max_height']
            );

            // Encode to WebP in memory
            ob_start();
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagewebp($image, null, $config['quality']);
            $webpContent = ob_get_clean();

            imagedestroy($image);

            if ($webpContent === false || strlen($webpContent) === 0) {
                return $file->store($directory, $targetDisk);
            }

            // Generate unique filename
            $filename = Str::random(40).'.webp';
            $path = $directory !== '' ? "{$directory}/{$filename}" : $filename;

            Storage::disk($targetDisk)->put($path, $webpContent);

            return $path;
        } catch (Throwable $e) {
            Log::warning("ImageOptimizerService failed to optimize image: {$e->getMessage()}", [
                'directory' => $directory,
                'profile' => $profile,
                'exception' => $e,
            ]);

            // Graceful fallback to raw file upload
            return $file->store($directory, $targetDisk);
        }
    }

    /**
     * Delete an existing image file from storage if present.
     */
    public function delete(?string $path, ?string $disk = null): bool
    {
        if (! $path) {
            return false;
        }

        $targetDisk = $this->resolveDisk($disk);

        // Normalize path if a full storage URL was supplied
        $storagePrefix = '/storage/';
        if (str_contains($path, $storagePrefix)) {
            $path = substr($path, strpos($path, $storagePrefix) + strlen($storagePrefix));
        }

        if (Storage::disk($targetDisk)->exists($path)) {
            return Storage::disk($targetDisk)->delete($path);
        }

        // Also check default disk if different
        if ($targetDisk !== config('filesystems.default') && Storage::disk(config('filesystems.default'))->exists($path)) {
            return Storage::disk(config('filesystems.default'))->delete($path);
        }

        return false;
    }

    /**
     * Resize image proportionally within the maximum bounding box.
     */
    protected function resizeProportionally(GdImage $image, int $maxWidth, int $maxHeight): GdImage
    {
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        if ($originalWidth <= 0 || $originalHeight <= 0) {
            return $image;
        }

        // No resize needed if image is already within bounds
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            return $image;
        }

        // Calculate proportional scale factor
        $scale = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $targetWidth = max(1, (int) round($originalWidth * $scale));
        $targetHeight = max(1, (int) round($originalHeight * $scale));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($resized === false) {
            return $image;
        }

        // Preserve alpha transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        if ($transparent !== false) {
            imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $originalWidth,
            $originalHeight
        );

        imagedestroy($image);

        return $resized;
    }

    /**
     * Automatically rotate image based on EXIF Orientation tag.
     */
    protected function autoRotateExif(GdImage $image, string $filePath): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        try {
            $exif = @exif_read_data($filePath);
            if (! $exif || ! isset($exif['Orientation'])) {
                return $image;
            }

            $orientation = (int) $exif['Orientation'];
            $degrees = match ($orientation) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($degrees !== 0) {
                $rotated = imagerotate($image, $degrees, 0);
                if ($rotated !== false) {
                    imagedestroy($image);

                    return $rotated;
                }
            }
        } catch (Throwable) {
            // Silently proceed if EXIF cannot be read
        }

        return $image;
    }

    /**
     * Resolve target filesystem disk.
     */
    protected function resolveDisk(?string $disk): string
    {
        if ($disk !== null && $disk !== '') {
            return $disk;
        }

        return config('filesystems.media_disk', config('filesystems.default', 'public'));
    }
}

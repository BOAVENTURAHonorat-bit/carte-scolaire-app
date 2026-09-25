<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageDataUri
{
    /**
     * Convert a storage disk path (or absolute path) to a base64 data URI,
     * so dompdf can embed the image without needing filesystem/URL access.
     */
    public static function fromStoragePath(?string $path, string $disk = 'public'): ?string
    {
        if (! $path) {
            return null;
        }

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);
        $mime = Storage::disk($disk)->mimeType($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    /**
     * Crop a stored image to the given aspect ratio (server-side, via GD) and
     * return it as a base64 data URI, positioned per $posX/$posY (0-100, same
     * meaning as CSS object-position). dompdf renders plain <img> tags much
     * more sharply than CSS background-image scaling, and it does not honor
     * object-fit/object-position at all (it just stretches), so cropping the
     * bitmap ourselves is what keeps photos both sharp and correctly framed.
     */
    public static function croppedDataUri(
        ?string $path,
        float $targetRatio,
        int $posX = 50,
        int $posY = 50,
        string $disk = 'public',
        int $maxDimension = 450
    ): ?string {
        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $lastModified = Storage::disk($disk)->lastModified($path);
        $cacheKey = md5($disk.'|'.$path.'|'.$lastModified.'|'.$targetRatio.'|'.$posX.'|'.$posY.'|'.$maxDimension);
        $cachePath = 'cache/cropped/'.$cacheKey.'.png';

        if (Storage::disk('public')->exists($cachePath)) {
            return 'data:image/png;base64,'.base64_encode(Storage::disk('public')->get($cachePath));
        }

        $contents = Storage::disk($disk)->get($path);
        $source = @imagecreatefromstring($contents);

        if (! $source) {
            // Format GD can't decode (e.g. some HEIC/webp builds) — fall back uncropped.
            return self::fromStoragePath($path, $disk);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $sourceRatio = $width / $height;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * $targetRatio);
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
        }

        $maxX = max(0, $width - $cropWidth);
        $maxY = max(0, $height - $cropHeight);
        $srcX = (int) round($maxX * max(0, min(100, $posX)) / 100);
        $srcY = (int) round($maxY * max(0, min(100, $posY)) / 100);

        $scale = min(1, $maxDimension / max($cropWidth, $cropHeight));
        $destWidth = max(1, (int) round($cropWidth * $scale));
        $destHeight = max(1, (int) round($cropHeight * $scale));

        $dest = imagecreatetruecolor($destWidth, $destHeight);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        imagecopyresampled(
            $dest, $source,
            0, 0, $srcX, $srcY,
            $destWidth, $destHeight, $cropWidth, $cropHeight
        );

        ob_start();
        imagepng($dest, null, 6);
        $data = ob_get_clean();

        imagedestroy($source);
        imagedestroy($dest);

        Storage::disk('public')->put($cachePath, $data);

        return 'data:image/png;base64,'.base64_encode($data);
    }

    /**
     * Convert a freshly-selected upload (not yet saved to disk) to a base64
     * data URI, downscaled for a light/fast payload. Used by the live draft
     * preview (élève/accompagnateur pas encore enregistrés) — pas de cache
     * disque ici, ce serait une donnée jetable à chaque frappe.
     */
    public static function fromUploadedFile(?\Illuminate\Http\UploadedFile $file, int $maxDimension = 600): ?string
    {
        if (! $file) {
            return null;
        }

        $contents = file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return 'data:'.($file->getMimeType() ?: 'image/png').';base64,'.base64_encode($contents);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxDimension / max($width, $height));
        $destWidth = max(1, (int) round($width * $scale));
        $destHeight = max(1, (int) round($height * $scale));

        $dest = imagecreatetruecolor($destWidth, $destHeight);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $destWidth, $destHeight, $width, $height);

        ob_start();
        imagepng($dest, null, 6);
        $data = ob_get_clean();

        imagedestroy($source);
        imagedestroy($dest);

        return 'data:image/png;base64,'.base64_encode($data);
    }

    /**
     * Return a blurred, downscaled copy of a stored image as a base64 data URI.
     * dompdf does not support the CSS `filter` property at all, so a watermark
     * blurred only via CSS shows sharp/unblurred in the PDF while looking correct
     * in the browser preview — baking the blur into the bitmap itself keeps both
     * renders identical.
     */
    public static function blurredDataUri(
        ?string $path,
        int $blurPasses = 6,
        int $maxDimension = 700,
        string $disk = 'public'
    ): ?string {
        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $lastModified = Storage::disk($disk)->lastModified($path);
        $cacheKey = md5($disk.'|'.$path.'|'.$lastModified.'|blur|'.$blurPasses.'|'.$maxDimension);
        $cachePath = 'cache/cropped/'.$cacheKey.'.png';

        if (Storage::disk('public')->exists($cachePath)) {
            return 'data:image/png;base64,'.base64_encode(Storage::disk('public')->get($cachePath));
        }

        $contents = Storage::disk($disk)->get($path);
        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return self::fromStoragePath($path, $disk);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxDimension / max($width, $height));
        $destWidth = max(1, (int) round($width * $scale));
        $destHeight = max(1, (int) round($height * $scale));

        $dest = imagecreatetruecolor($destWidth, $destHeight);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $destWidth, $destHeight, $width, $height);

        imagealphablending($dest, true);
        for ($i = 0; $i < $blurPasses; $i++) {
            imagefilter($dest, IMG_FILTER_GAUSSIAN_BLUR);
        }

        ob_start();
        imagepng($dest, null, 6);
        $data = ob_get_clean();

        imagedestroy($source);
        imagedestroy($dest);

        Storage::disk('public')->put($cachePath, $data);

        return 'data:image/png;base64,'.base64_encode($data);
    }
}

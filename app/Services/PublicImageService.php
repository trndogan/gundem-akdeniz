<?php

namespace App\Services;

use App\Helpers\PathHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PublicImageService
{
    /**
     * Görseli WebP olarak public/images/{baseDir}/Y/m/ altına kaydeder.
     *
     * @return string Göreli yol (örn. mayors/2026/02/kemal-ozdemir-a3b2.webp) — DB'de saklanacak format
     */
    public function saveAsWebp(UploadedFile $file, string $baseDir): string
    {
        $baseDir = trim($baseDir, '/');
        $directory = $baseDir . '/' . now()->format('Y/m');

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($originalName, '-');
        if ($slug === '') {
            $slug = 'gorsel';
        }

        $filename = $slug . '-' . Str::lower(Str::random(4)) . '.webp';

        $fullDir = PathHelper::getImagesPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
        if (! is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $destPath = $fullDir . DIRECTORY_SEPARATOR . $filename;

        $srcPath = $file->getRealPath();
        $mime = $file->getMimeType();
        $src = match (true) {
            str_contains((string) $mime, 'png') => @imagecreatefrompng($srcPath),
            str_contains((string) $mime, 'gif') => @imagecreatefromgif($srcPath),
            str_contains((string) $mime, 'webp') => @imagecreatefromwebp($srcPath),
            default => @imagecreatefromjpeg($srcPath),
        };

        if ($src === false) {
            throw new \RuntimeException('Görsel işlenemedi.');
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxW = 1200;

        if ($origW > $maxW) {
            $newW = $maxW;
            $newH = (int) round($origH * ($maxW / $origW));
            $dst = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        imagewebp($src, $destPath, 80);
        imagedestroy($src);

        return $directory . '/' . $filename;
    }
}

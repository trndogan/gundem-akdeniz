<?php

namespace App\Helpers;

class PathHelper
{
    /**
     * Public kök dizinini döndürür (paylaşımlı hosting uyumlu).
     * Öncelik: DOCUMENT_ROOT, public_html, base_path/public
     */
    public static function getPublicPath(): string
    {
        if (! empty($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT'])) {
            return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
        }

        $publicHtml = base_path('public_html');
        if (is_dir($publicHtml)) {
            return $publicHtml;
        }

        return public_path();
    }

    /**
     * Public içindeki images klasörünün tam dosya yolunu döndürür.
     */
    public static function getImagesPath(): string
    {
        return rtrim(self::getPublicPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'images';
    }

    /**
     * Veritabanında saklanan göreli yol (örn. mayors/2026/02/x.webp) için tam dosya yolu — images altında.
     */
    public static function absoluteImagesFilePath(string $relativePath): string
    {
        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        return self::getImagesPath()            . DIRECTORY_SEPARATOR . $relativePath;
    }

    /**
     * Laravel storage/app/public altındaki göreli yol için tam dosya yolu.
     */
    public static function absoluteStoragePublicFilePath(string $relativePath): string
    {
        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        return storage_path('app/public/' . $relativePath);
    }

    /**
     * public/storage symlink köprüsü üzerinden erişilen yol (varsa).
     */
    public static function absolutePublicStorageFilePath(string $relativePath): string
    {
        $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        return public_path('storage/' . $relativePath);
    }

    /**
     * Upload sonrası DB'de saklanan göreli yol için doğru tarayıcı URL'si.
     * Önce public/images, yoksa mevcut storage yüklemeleri (geriye dönük).
     */
    public static function urlForStoredUpload(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset(ltrim($path, '/'));
        }

        $relative = ltrim($path, '/');

        // Bazı kayıtlarda veritabanı değeri "storage/..." veya "images/..." ile gelebilir.
        // Dosya sisteminde arama yapmadan önce prefix'i normalize edelim.
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }
        if (str_starts_with($relative, 'images/')) {
            $relative = substr($relative, strlen('images/'));
        }

        if (is_file(self::absoluteImagesFilePath($relative))) {
            return asset('images/' . $relative);
        }

        if (is_file(self::absoluteStoragePublicFilePath($relative))) {
            return asset('storage/' . $relative);
        }

        if (is_file(self::absolutePublicStorageFilePath($relative))) {
            return asset('storage/' . $relative);
        }

        return asset('images/' . $relative);
    }

    public static function storedUploadFileExists(?string $path): bool
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return (bool) $path;
        }

        if (str_starts_with($path, '/')) {
            return is_file(self::getPublicPath() . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));
        }

        $relative = ltrim($path, '/');

        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        if (str_starts_with($relative, 'images/')) {
            $relative = substr($relative, strlen('images/'));
        }

        return is_file(self::absoluteImagesFilePath($relative))
            || is_file(self::absoluteStoragePublicFilePath($relative))
            || is_file(self::absolutePublicStorageFilePath($relative));
    }

    /**
     * Yerelde saklanan bir upload dosyasını siler.
     * Dış URL'ler asla silinmez.
     */
    public static function deleteStoredUploadFile(?string $path): bool
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        // /storage/... veya /images/... gibi prefix'ler gelirse normalize edelim.
        $relative = $path;
        if (str_starts_with($relative, '/')) {
            $relative = ltrim($relative, '/');
        }

        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        if (str_starts_with($relative, 'images/')) {
            $relative = substr($relative, strlen('images/'));
        }

        $pathsToTry = [
            self::absoluteImagesFilePath($relative),
            self::absoluteStoragePublicFilePath($relative),
            self::absolutePublicStorageFilePath($relative),
        ];

        $deleted = false;

        foreach ($pathsToTry as $filePath) {
            if (is_file($filePath)) {
                @unlink($filePath);
                $deleted = true;
            }
        }

        return $deleted;
    }
}

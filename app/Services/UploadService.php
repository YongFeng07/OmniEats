<?php

namespace App\Services;

/**
 * Secure image upload handling (Modules 2 & 3).
 *
 * Safety checks: upload error, file size limit, extension whitelist
 * AND a real MIME-type check, then a random filename so an attacker
 * cannot guess paths. Files land in public/uploads/<subdir>/, where
 * .htaccess blocks PHP execution.
 */
class UploadService
{
    public const MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    /** Extension => expected MIME type (checked against the real file). */
    private const ALLOWED = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * Store an uploaded image.
     *
     * Returns ['ok' => true, 'path' => null] when no file was submitted,
     * ['ok' => true, 'path' => 'uploads/<subdir>/<name>'] on success, or
     * ['ok' => false, 'path' => null] after flashing the error message.
     */
    public static function store(array $file, string $subdir): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => null];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            flash('error', 'The image upload failed. Please try again.');
            return ['ok' => false, 'path' => null];
        }

        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            flash('error', 'The image is too large (maximum 2 MB).');
            return ['ok' => false, 'path' => null];
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $expectedMime = self::ALLOWED[$extension] ?? null;
        $actualMime = function_exists('mime_content_type') && !empty($file['tmp_name'])
            ? mime_content_type($file['tmp_name'])
            : null;

        if ($expectedMime === null || $actualMime !== $expectedMime) {
            flash('error', 'Only JPG, PNG or WebP images are allowed.');
            return ['ok' => false, 'path' => null];
        }

        // app/Services → project root is TWO levels up.
        $dir = dirname(__DIR__, 2) . '/public/uploads/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = bin2hex(random_bytes(8)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            flash('error', 'The image could not be saved. Please try again.');
            return ['ok' => false, 'path' => null];
        }

        return ['ok' => true, 'path' => 'uploads/' . $subdir . '/' . $name];
    }
}

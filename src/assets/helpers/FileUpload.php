<?php

namespace App\Helpers;

class FileUpload
{
    /**
     * Accepts $_FILES element, returns public URL or error.
     */
    public static function store(array $file): string
    {
        // Absolute directory (one level above “api”)
        $dir = dirname(__DIR__, 2) . '/media/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Whitelist
        $allowed = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ];
        if (!in_array($file['type'], $allowed)) {
            throw new \RuntimeException('Invalid file type.');
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $path = $dir . $name;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \RuntimeException('Move failed.');
        }

        // Public URL (change host if you deploy elsewhere)
        return 'https://api-wholeness.ai/src/media/' . $name;
    }
}
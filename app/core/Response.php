<?php

namespace App\Core;

class Response
{
    public static function json(array $data, int $status = 200): void
    {
        self::cleanBuffer();
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function download(string $content, string $filename, string $contentType): void
    {
        self::cleanBuffer();
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public static function downloadFile(string $filePath, string $filename): void
    {
        self::cleanBuffer();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        unlink($filePath);
        exit;
    }

    public static function redirect(string $url): void
    {
        self::cleanBuffer();
        header('Location: ' . $url);
        exit;
    }

    public static function error(string $message, int $status = 400): void
    {
        self::json(['success' => false, 'error' => $message], $status);
    }

    public static function success(mixed $data = null): void
    {
        self::json(['success' => true] + (is_array($data) ? $data : ['data' => $data]));
    }

    private static function cleanBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
<?php

namespace App\Helpers;

class Utils
{
    /**
     * Send JSON success response
     *
     * @param string $message The success message
     * @param array $data Additional data to include in the response
     * @param int $statusCode HTTP status code (default: 200)
     * @return string JSON-encoded response
     */
    public static function sendSuccessResponse(string $message, array $data = [], int $statusCode = 200): string
    {
        http_response_code($statusCode);
        return json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send JSON error response
     *
     * @param string $message The error message
     * @param int $statusCode HTTP status code
     * @return string JSON-encoded response
     */
    public static function sendErrorResponse(string $message, int $statusCode): string
    {
        http_response_code($statusCode);
        return json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }
}
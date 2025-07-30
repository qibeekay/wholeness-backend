<?php
namespace App\Helpers;

class AuthGuard
{
    public static function checkBearer(): bool
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']  // Apache fallback
            ?? $_SERVER['HTTP_X_AUTHORIZATION']
            ?? '';

        $token = trim(str_ireplace('Bearer ', '', $auth));
        $expected = $_ENV['AUTH_TOKEN'] ?? '';

        return $token === $expected;
    }

    public static function guard(): void
    {
        if (!self::checkBearer()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
    }
}
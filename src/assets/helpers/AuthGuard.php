<?php

namespace App\Helpers;

class AuthGuard
{
    /* ---- static shared-token guard (no session) ---- */
    public static function checkBearer(): bool
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['HTTP_X_AUTHORIZATION']
            ?? '';

        $token = trim(str_ireplace('Bearer ', '', $auth));
        $expected = $_ENV['AUTH_TOKEN'] ?? '';

        return $token === $expected;
    }

    public static function guardBearer(): void
    {
        if (!self::checkBearer()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
    }

    /* ---- session-based guard for user routes ---- */
    public static function guardUser(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user']['id'])) {   // make sure id is there
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
            exit;
        }
    }

    /**
     * Return the id of the authenticated user.
     * Call guardUser() first to be sure the session exists.
     */
    public static function userId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }
}

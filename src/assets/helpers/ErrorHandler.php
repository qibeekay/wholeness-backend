<?php

namespace App\Helpers;

use ErrorException;
use Throwable;

class ErrorHandler
{
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline)
    {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleException(Throwable $exception): void
    {
        // Use Utils to format the error response
        echo Utils::sendErrorResponse(
            sprintf(
                '%s in %s on line %d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ),
            500
        );
    }
}
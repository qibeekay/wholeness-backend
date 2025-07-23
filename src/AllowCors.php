<?php

class AllowCors
{
    private const ALLOW_CORS_ORIGIN_KEY = 'Access-Control-Allow-Origin';

    private const ALLOW_CORS_METHOD_KEY = 'Access-Control-Allow-Methods';

    private const ALLOW_CORS_ORIGIN_VALUE = '*';

    private const ALLOW_CORS_METHODS_VALUE = 'GET, POST, PUT, DELETE, PATCH, OPTIONS';

    /**
     * Initializze the Cross-Origin Resourse Sharing (CORS) headers
     * @return void
     */
    public function init(): void
    {
        $this->set(self::ALLOW_CORS_ORIGIN_KEY, self::ALLOW_CORS_ORIGIN_VALUE);
        $this->set(self::ALLOW_CORS_METHOD_KEY, self::ALLOW_CORS_METHODS_VALUE);
    }

    private function set(string $key, string $value)
    {
        header("{$key}:{$value}");
    }
}
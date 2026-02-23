<?php

namespace App\Support;

class RequestContext
{
    private static ?string $requestId = null;

    public static function ensureRequestId(): string
    {
        if (self::$requestId !== null && self::$requestId !== '') {
            return self::$requestId;
        }

        try {
            self::$requestId = bin2hex(random_bytes(8));
        } catch (\Throwable $exception) {
            self::$requestId = substr(hash('sha256', uniqid('req_', true)), 0, 16);
        }

        return self::$requestId;
    }

    public static function getRequestId(): string
    {
        return self::ensureRequestId();
    }

    public static function setRequestId(string $requestId): void
    {
        $requestId = trim($requestId);
        if ($requestId !== '') {
            self::$requestId = $requestId;
        }
    }
}

<?php

namespace App\Support;

class AppLogger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $payload = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            $encoded = sprintf(
                '[%s] %s %s',
                strtoupper($level),
                $message,
                RequestContext::getRequestId()
            );
        }

        error_log($encoded);
    }
}

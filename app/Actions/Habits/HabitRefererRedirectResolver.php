<?php

namespace App\Actions\Habits;

final class HabitRefererRedirectResolver
{
    public static function resolve(array $server, string $fallback = '../public/habits.php'): string
    {
        $referer = $server['HTTP_REFERER'] ?? '';
        if (!is_string($referer) || $referer === '') {
            return $fallback;
        }

        $parts = parse_url($referer);
        if ($parts === false) {
            return $fallback;
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '' || $path[0] !== '/' || str_contains($path, "\r") || str_contains($path, "\n")) {
            return $fallback;
        }

        $requestHost = strtolower((string) ($server['HTTP_HOST'] ?? ''));
        $refererHost = strtolower((string) ($parts['host'] ?? ''));

        if ($refererHost !== '' && $requestHost !== '' && !hash_equals($requestHost, $refererHost)) {
            return $fallback;
        }

        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $path . $query;
    }
}

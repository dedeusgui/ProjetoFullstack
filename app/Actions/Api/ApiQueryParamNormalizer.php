<?php

namespace App\Actions\Api;

final class ApiQueryParamNormalizer
{
    public static function normalizeStatsView(mixed $view): string
    {
        return self::normalizeStringOption($view, ['dashboard', 'history'], 'dashboard');
    }

    public static function normalizeHabitsScope(mixed $scope): string
    {
        return self::normalizeStringOption($scope, ['all', 'today', 'page'], 'all');
    }

    /**
     * @param list<string> $allowed
     */
    private static function normalizeStringOption(mixed $value, array $allowed, string $default): string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return in_array($normalized, $allowed, true) ? $normalized : $default;
    }
}

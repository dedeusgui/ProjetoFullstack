<?php

class TrendAnalyzer
{
    public static function detect(array $behaviorData): array
    {
        $avg7 = (float) ($behaviorData['avg_daily_7'] ?? 0);
        $avg30 = (float) ($behaviorData['avg_daily_30'] ?? 0);
        $delta = round($avg7 - $avg30, 2);
        $totalHabits = (int) ($behaviorData['total_habits'] ?? 0);
        $consecutiveFailures = (int) ($behaviorData['consecutive_failures'] ?? 0);
        $dailySeries = $behaviorData['daily_series_30'] ?? [];

        $todayCompleted = 0;
        if (!empty($dailySeries)) {
            $lastDay = end($dailySeries);
            $todayCompleted = (int) ($lastDay['completed'] ?? 0);
        }

        if ($delta > 0.1) {
            $trend = 'positive';
        } elseif ($delta < -0.1) {
            $trend = 'negative';
        } else {
            $trend = 'neutral';
        }

        if ($trend === 'positive' && $totalHabits > 0 && $todayCompleted === 0 && $consecutiveFailures > 0) {
            $trend = 'neutral';
        }

        $consistency = self::calculateConsistency($dailySeries);

        return [
            'trend' => $trend,
            'avg_7' => $avg7,
            'avg_30' => $avg30,
            'delta' => $delta,
            'consistency' => $consistency
        ];
    }

    private static function calculateConsistency(array $dailySeries): float
    {
        if (count($dailySeries) === 0) {
            return 0.0;
        }

        $activeDays = 0;
        foreach ($dailySeries as $day) {
            if (($day['completed'] ?? 0) > 0) {
                $activeDays++;
            }
        }

        return round(($activeDays / count($dailySeries)) * 100, 2);
    }
}

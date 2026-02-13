<?php

class TrendAnalyzer
{
    public static function detect(array $behaviorData): array
    {
        $avg7 = (float) ($behaviorData['avg_daily_7'] ?? 0);
        $avg30 = (float) ($behaviorData['avg_daily_30'] ?? 0);
        $delta = round($avg7 - $avg30, 2);

        if ($delta > 0.1) {
            $trend = 'positive';
        } elseif ($delta < -0.1) {
            $trend = 'negative';
        } else {
            $trend = 'neutral';
        }

        $series = $behaviorData['daily_series_30'] ?? [];
        $consistency = self::calculateConsistency($series);

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

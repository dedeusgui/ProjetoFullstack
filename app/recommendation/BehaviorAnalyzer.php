<?php

namespace App\Recommendation;

class BehaviorAnalyzer
{
    public static function analyze(\mysqli $conn, int $userId, ?string $referenceDate = null): array
    {
        $today = self::resolveReferenceDate($referenceDate);
        $start7 = $today->sub(new \DateInterval('P6D'));
        $start30 = $today->sub(new \DateInterval('P29D'));

        $habitRows = self::getRelevantHabits($conn, $userId, $start30, $today);
        $completionsByDate = self::getCompletionsByDate($conn, $userId, $start30, $today);
        $totalCompletionsAllTime = self::getTotalCompletions($conn, $userId);
        $currentStreak = self::getCurrentStreak($conn, $userId);

        $period7 = self::calculatePeriodMetrics($habitRows, $completionsByDate, $start7, $today);
        $period30 = self::calculatePeriodMetrics($habitRows, $completionsByDate, $start30, $today);

        $oldestHabitDate = self::getOldestHabitDate($conn, $userId, $today);
        $allTimeExpected = self::calculateExpectedCompletions($habitRows, $oldestHabitDate, $today);
        $allTimeRate = $allTimeExpected > 0
            ? round(($totalCompletionsAllTime / $allTimeExpected) * 100, 2)
            : 0.0;

        return [
            'total_habits' => count($habitRows),
            'current_streak' => $currentStreak,
            'completed_7' => $period7['completed'],
            'expected_7' => $period7['expected'],
            'completion_rate_7' => $period7['rate'],
            'avg_daily_7' => $period7['avg_daily'],
            'completed_30' => $period30['completed'],
            'expected_30' => $period30['expected'],
            'completion_rate_30' => $period30['rate'],
            'avg_daily_30' => $period30['avg_daily'],
            'completion_rate_all_time' => $allTimeRate,
            'total_completions_all_time' => $totalCompletionsAllTime,
            'consecutive_failures' => self::calculateConsecutiveFailures($completionsByDate, $today, 30),
            'daily_series_30' => self::buildDailySeries($completionsByDate, $start30, $today)
        ];
    }

    private static function getRelevantHabits(\mysqli $conn, int $userId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $stmt = $conn->prepare(
            "SELECT id, frequency, target_days, start_date, end_date
            FROM habits
            WHERE user_id = ?
              AND (start_date IS NULL OR start_date <= ?)
              AND (end_date IS NULL OR end_date >= ?)"
        );

        $endDate = $end->format('Y-m-d');
        $startDate = $start->format('Y-m-d');
        $stmt->bind_param('iss', $userId, $endDate, $startDate);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private static function getCompletionsByDate(\mysqli $conn, int $userId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $stmt = $conn->prepare(
            "SELECT DATE(completion_date) AS completion_date, COUNT(*) AS completed
            FROM habit_completions
            WHERE user_id = ?
              AND completion_date BETWEEN ? AND ?
            GROUP BY DATE(completion_date)"
        );

        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[$row['completion_date']] = (int) $row['completed'];
        }

        return $rows;
    }

    private static function calculatePeriodMetrics(array $habitRows, array $completionsByDate, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $expected = self::calculateExpectedCompletions($habitRows, $start, $end);
        $completed = 0;
        $cursor = $start;

        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $completed += $completionsByDate[$key] ?? 0;
            $cursor = $cursor->add(new \DateInterval('P1D'));
        }

        $days = $start->diff($end)->days + 1;
        $rate = $expected > 0 ? round(($completed / $expected) * 100, 2) : 0.0;

        return [
            'completed' => $completed,
            'expected' => $expected,
            'rate' => $rate,
            'avg_daily' => $days > 0 ? round($completed / $days, 2) : 0.0
        ];
    }

    private static function calculateExpectedCompletions(array $habitRows, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        $expected = 0;

        foreach ($habitRows as $habit) {
            $habitStart = !empty($habit['start_date']) ? new \DateTimeImmutable($habit['start_date']) : $start;
            $habitEnd = !empty($habit['end_date']) ? new \DateTimeImmutable($habit['end_date']) : $end;

            $effectiveStart = $habitStart > $start ? $habitStart : $start;
            $effectiveEnd = $habitEnd < $end ? $habitEnd : $end;

            if ($effectiveStart > $effectiveEnd) {
                continue;
            }

            $frequency = $habit['frequency'] ?? 'daily';
            $targetDays = self::decodeTargetDays($habit['target_days'] ?? null);

            $cursor = $effectiveStart;
            while ($cursor <= $effectiveEnd) {
                if (self::isExpectedDay($frequency, $targetDays, $cursor)) {
                    $expected++;
                }
                $cursor = $cursor->add(new \DateInterval('P1D'));
            }
        }

        return $expected;
    }

    private static function decodeTargetDays(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_map('intval', $decoded);
    }

    private static function isExpectedDay(string $frequency, array $targetDays, \DateTimeImmutable $day): bool
    {
        if ($frequency === 'daily') {
            return true;
        }

        if ($frequency === 'weekly' || $frequency === 'custom') {
            if (empty($targetDays)) {
                return true;
            }

            $weekDay = (int) $day->format('w');
            return in_array($weekDay, $targetDays, true);
        }

        return true;
    }

    private static function calculateConsecutiveFailures(array $completionsByDate, \DateTimeImmutable $today, int $maxWindow): int
    {
        $failures = 0;

        for ($i = 0; $i < $maxWindow; $i++) {
            $date = $today->sub(new \DateInterval('P' . $i . 'D'))->format('Y-m-d');
            if (($completionsByDate[$date] ?? 0) > 0) {
                break;
            }
            $failures++;
        }

        return $failures;
    }

    private static function buildDailySeries(array $completionsByDate, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $series = [];
        $cursor = $start;

        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $series[] = [
                'date' => $key,
                'completed' => $completionsByDate[$key] ?? 0
            ];
            $cursor = $cursor->add(new \DateInterval('P1D'));
        }

        return $series;
    }

    private static function getTotalCompletions(\mysqli $conn, int $userId): int
    {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM habit_completions WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int) ($row['total'] ?? 0);
    }

    private static function getCurrentStreak(\mysqli $conn, int $userId): int
    {
        $stmt = $conn->prepare('SELECT COALESCE(MAX(current_streak), 0) AS streak FROM habits WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int) ($row['streak'] ?? 0);
    }

    private static function resolveReferenceDate(?string $referenceDate): \DateTimeImmutable
    {
        if (!empty($referenceDate)) {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $referenceDate);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed;
            }
        }

        return new \DateTimeImmutable('today');
    }

    private static function getOldestHabitDate(\mysqli $conn, int $userId, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        $stmt = $conn->prepare('SELECT MIN(start_date) AS oldest FROM habits WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!empty($row['oldest'])) {
            return new \DateTimeImmutable($row['oldest']);
        }

        return $fallback;
    }
}

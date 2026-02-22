<?php

namespace App\Habits;

class HabitSchedulePolicy
{
    public static function normalizeTargetDays(?string $targetDays): array
    {
        if (empty($targetDays)) {
            return [];
        }

        $decoded = json_decode($targetDays, true);
        if (!is_array($decoded)) {
            return [];
        }

        $days = array_values(array_unique(array_map('intval', $decoded)));
        return array_values(array_filter($days, static fn($day) => $day >= 0 && $day <= 6));
    }

    public static function isScheduledForDate(array $habit, string $date): bool
    {
        $targetDate = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$targetDate) {
            return false;
        }

        if (!empty($habit['start_date']) && $date < $habit['start_date']) {
            return false;
        }

        if (!empty($habit['end_date']) && $date > $habit['end_date']) {
            return false;
        }

        $frequency = $habit['frequency'] ?? 'daily';
        if ($frequency === 'daily') {
            return true;
        }

        $phpWeekDay = (int) $targetDate->format('w');

        if ($frequency === 'weekly') {
            $days = self::normalizeTargetDays($habit['target_days'] ?? null);
            if (empty($days)) {
                return $phpWeekDay === (int) date('w', strtotime((string) ($habit['start_date'] ?? $date)));
            }
            return in_array($phpWeekDay, $days, true);
        }

        if ($frequency === 'custom') {
            $days = self::normalizeTargetDays($habit['target_days'] ?? null);
            if (empty($days)) {
                return false;
            }
            return in_array($phpWeekDay, $days, true);
        }

        return true;
    }

    public static function getNextDueDate(array $habit, ?string $fromDate = null, ?string $fallbackToday = null): ?string
    {
        $baseDate = $fromDate ?? $fallbackToday ?? date('Y-m-d');
        $date = \DateTime::createFromFormat('Y-m-d', $baseDate);
        if (!$date) {
            return null;
        }

        for ($i = 0; $i < 366; $i++) {
            $candidate = $date->format('Y-m-d');
            if (self::isScheduledForDate($habit, $candidate)) {
                return $candidate;
            }
            $date->modify('+1 day');
        }

        return null;
    }
}

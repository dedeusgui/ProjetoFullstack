<?php

namespace App\Stats;

use App\Habits\HabitSchedulePolicy;
use App\Repository\HabitQueryRepository;
use App\Repository\StatsRepository;
use App\Support\UserLocalDateResolver;

class StatsQueryService
{
    private \mysqli $conn;
    private StatsRepository $statsRepository;
    private HabitQueryRepository $habitQueryRepository;
    private UserLocalDateResolver $userLocalDateResolver;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->statsRepository = new StatsRepository($conn);
        $this->habitQueryRepository = new HabitQueryRepository($conn);
        $this->userLocalDateResolver = new UserLocalDateResolver($conn);
    }

    public function getUserTodayDate(int $userId): string
    {
        return $this->userLocalDateResolver->getTodayDateForUser($userId);
    }

    public function getTotalHabits(int $userId): int
    {
        return $this->habitQueryRepository->countActiveHabits($userId);
    }

    public function getCompletedToday(int $userId, ?string $date = null): int
    {
        $targetDate = $date ?? $this->getUserTodayDate($userId);
        return $this->statsRepository->countCompletedHabitsOnDate($userId, $targetDate);
    }

    public function getCompletionRate(int $userId): int
    {
        $summary = $this->getCompletionWindowSummary($userId);
        return (int) ($summary['rate'] ?? 0);
    }

    public function getCompletionTrend(int $userId, int $windowDays = 7): array
    {
        $period = max(1, $windowDays);

        $today = $this->getUserTodayDate($userId);
        $currentEnd = $today;
        $currentStart = $this->getCompletionWindowStartDate($userId, $period, $currentEnd);

        if ($currentStart === null) {
            return ['status' => 'insufficient', 'label' => 'Dados insuficientes', 'icon' => 'bi-dash', 'delta' => 0];
        }

        $current = $this->getCompletionSummaryByRange($userId, $currentStart, $currentEnd);

        $previousEnd = date('Y-m-d', strtotime($currentStart . ' -1 day'));
        $previousStart = date('Y-m-d', strtotime('-' . ($period - 1) . ' days', strtotime($previousEnd)));

        $createdDate = $this->getCompletionWindowStartDate($userId, 0, $today);
        if ($createdDate === null || $previousEnd < $createdDate) {
            return ['status' => 'insufficient', 'label' => 'Dados insuficientes', 'icon' => 'bi-dash', 'delta' => 0];
        }

        if ($previousStart < $createdDate) {
            $previousStart = $createdDate;
        }

        $previous = $this->getCompletionSummaryByRange($userId, $previousStart, $previousEnd);

        if (($current['scheduled'] ?? 0) === 0 || ($previous['scheduled'] ?? 0) === 0) {
            return ['status' => 'insufficient', 'label' => 'Dados insuficientes', 'icon' => 'bi-dash', 'delta' => 0];
        }

        $delta = (int) (($current['rate'] ?? 0) - ($previous['rate'] ?? 0));
        if ($delta > 0) {
            return ['status' => 'up', 'label' => '+' . $delta . '% vs semana anterior', 'icon' => 'bi-arrow-up', 'delta' => $delta];
        }
        if ($delta < 0) {
            return ['status' => 'down', 'label' => $delta . '% vs semana anterior', 'icon' => 'bi-arrow-down', 'delta' => $delta];
        }

        return ['status' => 'stable', 'label' => 'Sem alteração vs semana anterior', 'icon' => 'bi-dash', 'delta' => 0];
    }

    public function getCompletionWindowSummary(int $userId, int $days = 0): array
    {
        $today = $this->getUserTodayDate($userId);
        $startDate = $this->getCompletionWindowStartDate($userId, $days, $today);

        if ($startDate === null || $startDate > $today) {
            return ['rate' => 0, 'completed' => 0, 'scheduled' => 0, 'days_analyzed' => 0];
        }

        return $this->getCompletionSummaryByRange($userId, $startDate, $today);
    }

    public function getCurrentStreak(int $userId): int
    {
        $dates = $this->statsRepository->findDistinctCompletionDatesDesc($userId);
        if ($dates === []) {
            return 0;
        }

        $streak = 0;
        $checkDate = new \DateTime($this->getUserTodayDate($userId));

        foreach ($dates as $dateStr) {
            $completionDate = new \DateTime($dateStr);
            $diff = $checkDate->diff($completionDate)->days;
            if ($diff <= 1) {
                $streak++;
                $checkDate = $completionDate;
                continue;
            }
            break;
        }

        return $streak;
    }

    public function getTodayHabits(int $userId, ?string $targetDate = null): array
    {
        $date = $targetDate ?? $this->getUserTodayDate($userId);
        $rows = $this->habitQueryRepository->findActiveHabitsOrderedForDay($userId, $date);

        return array_values(array_filter($rows, static fn(array $habit): bool => HabitSchedulePolicy::isScheduledForDate($habit, $date)));
    }

    public function getActiveDays(int $userId): int
    {
        return $this->statsRepository->countActiveDays($userId);
    }

    public function getMonthlyData(int $userId, int $days = 30): array
    {
        $days = max(1, $days);
        $today = $this->getUserTodayDate($userId);
        $startDate = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));

        $completionRows = $this->statsRepository->findDailyCompletionCounts($userId, $startDate, $today);
        $completions = [];
        foreach ($completionRows as $row) {
            $completions[(string) $row['date']] = (int) ($row['completed'] ?? 0);
        }

        $totalHabits = $this->getTotalHabits($userId);
        $monthlyData = ['labels' => [], 'completed' => [], 'total' => []];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime($today . " -$i days"));
            $monthlyData['labels'][] = date('j', strtotime($date));
            $monthlyData['completed'][] = $completions[$date] ?? 0;
            $monthlyData['total'][] = $totalHabits;
        }

        return $monthlyData;
    }

    public function getTotalCompletions(int $userId): int
    {
        return $this->statsRepository->countTotalCompletions($userId);
    }

    public function getBestStreak(int $userId): int
    {
        return $this->statsRepository->findBestStreak($userId);
    }

    public function getCategoryStats(int $userId): array
    {
        return $this->statsRepository->findCategoryStats($userId);
    }

    public function getUserCreatedAt(int $userId): ?string
    {
        return $this->statsRepository->findUserCreatedAt($userId);
    }

    public function getRecentHistory(int $userId, int $days = 10, ?string $userCreatedAt = null): array
    {
        $maxDays = max(1, $days);
        $today = $this->getUserTodayDate($userId);

        if (!empty($userCreatedAt)) {
            $createdDate = date('Y-m-d', strtotime($userCreatedAt));
            $diffSeconds = strtotime($today) - strtotime($createdDate);
            if ($diffSeconds >= 0) {
                $daysSinceCreation = (int) floor($diffSeconds / 86400) + 1;
                $maxDays = min($maxDays, max(1, $daysSinceCreation));
            }
        }

        $startDate = date('Y-m-d', strtotime($today . ' -' . ($maxDays - 1) . ' days'));
        $rows = $this->statsRepository->findRecentHistory($userId, $startDate, $today);

        return array_map(static function (array $row): array {
            return [
                'date' => $row['date'],
                'completed' => (int) ($row['completed'] ?? 0),
                'total' => (int) ($row['total'] ?? 0),
                'percentage' => (float) ($row['percentage'] ?? 0),
            ];
        }, $rows);
    }

    private function getCompletionWindowStartDate(int $userId, int $days, string $referenceDate): ?string
    {
        $userCreatedAt = $this->getUserCreatedAt($userId);
        if (empty($userCreatedAt)) {
            return null;
        }

        $createdDate = date('Y-m-d', strtotime($userCreatedAt));
        $maxDays = max(0, $days);

        if ($maxDays === 0) {
            $firstCompletion = $this->statsRepository->findFirstCompletionDate($userId);
            if ($firstCompletion !== null && $firstCompletion > $createdDate) {
                return $firstCompletion;
            }
            return $createdDate;
        }

        $windowStart = date('Y-m-d', strtotime('-' . ($maxDays - 1) . ' days', strtotime($referenceDate)));
        return $windowStart > $createdDate ? $windowStart : $createdDate;
    }

    private function getCompletionSummaryByRange(int $userId, string $startDate, string $endDate): array
    {
        if ($startDate > $endDate) {
            return ['rate' => 0, 'completed' => 0, 'scheduled' => 0, 'days_analyzed' => 0];
        }

        $daysAnalyzed = (int) max(0, (strtotime($endDate) - strtotime($startDate)) / 86400) + 1;

        $habits = $this->statsRepository->findHabitsForCompletionWindow($userId, $startDate, $endDate);
        $scheduled = 0;

        if ($habits !== []) {
            $current = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');

                foreach ($habits as $habit) {
                    $habitStart = max($startDate, date('Y-m-d', strtotime((string) $habit['created_at'])));
                    $archivedDate = !empty($habit['archived_at'])
                        ? date('Y-m-d', strtotime((string) $habit['archived_at'] . ' -1 day'))
                        : $endDate;
                    $habitEnd = min($endDate, $archivedDate);

                    if ($dateStr >= $habitStart && $dateStr <= $habitEnd && HabitSchedulePolicy::isScheduledForDate($habit, $dateStr)) {
                        $scheduled++;
                    }
                }

                $current->modify('+1 day');
            }
        }

        $completed = $this->statsRepository->countCompletedHabitOccurrencesInRange($userId, $startDate, $endDate);
        $rate = $scheduled > 0 ? round(($completed / $scheduled) * 100) : 0;

        return [
            'rate' => (int) $rate,
            'completed' => $completed,
            'scheduled' => $scheduled,
            'days_analyzed' => $daysAnalyzed,
        ];
    }
}

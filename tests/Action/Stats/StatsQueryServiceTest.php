<?php

declare(strict_types=1);

namespace Tests\Action\Stats;

use App\Stats\StatsQueryService;
use Tests\Support\ActionTestCase;

final class StatsQueryServiceTest extends ActionTestCase
{
    public function testCompletionTrendReturnsInsufficientWhenUserBaselineIsMissing(): void
    {
        $service = new StatsQueryService($this->conn());

        $result = $service->getCompletionTrend(999999, 7);

        self::assertSame('insufficient', $result['status'] ?? null);
        self::assertSame(0, $result['delta'] ?? null);
    }

    public function testCompletionTrendReturnsInsufficientWhenScheduledCountsAreZero(): void
    {
        $userId = $this->createUserAtOffsetDays(-30);
        $service = new StatsQueryService($this->conn());

        $result = $service->getCompletionTrend($userId, 7);

        self::assertSame('insufficient', $result['status'] ?? null);
    }

    public function testCompletionTrendReturnsUpStatus(): void
    {
        [$service, $userId, $habitId, $today] = $this->createTrendFixture();
        $this->seedCompletionDates($habitId, $userId, [
            $this->shiftDate($today, -1),
            $this->shiftDate($today, -2),
            $this->shiftDate($today, -3),
            $this->shiftDate($today, -5),
            $today,
            $this->shiftDate($today, -8),
            $this->shiftDate($today, -12),
        ]);

        $result = $service->getCompletionTrend($userId, 7);

        self::assertSame('up', $result['status'] ?? null);
        self::assertGreaterThan(0, (int) ($result['delta'] ?? 0));
    }

    public function testCompletionTrendReturnsDownStatus(): void
    {
        [$service, $userId, $habitId, $today] = $this->createTrendFixture();
        $this->seedCompletionDates($habitId, $userId, [
            $this->shiftDate($today, -7),
            $this->shiftDate($today, -8),
            $this->shiftDate($today, -9),
            $this->shiftDate($today, -10),
            $this->shiftDate($today, -11),
            $today,
            $this->shiftDate($today, -2),
        ]);

        $result = $service->getCompletionTrend($userId, 7);

        self::assertSame('down', $result['status'] ?? null);
        self::assertLessThan(0, (int) ($result['delta'] ?? 0));
    }

    public function testCompletionTrendReturnsStableStatus(): void
    {
        [$service, $userId, $habitId, $today] = $this->createTrendFixture();
        $this->seedCompletionDates($habitId, $userId, [
            $today,
            $this->shiftDate($today, -2),
            $this->shiftDate($today, -7),
            $this->shiftDate($today, -9),
        ]);

        $result = $service->getCompletionTrend($userId, 7);

        self::assertSame('stable', $result['status'] ?? null);
        self::assertSame(0, (int) ($result['delta'] ?? -1));
    }

    public function testCompletionWindowSummaryReturnsZeroesWhenUserCreationIsMissing(): void
    {
        $service = new StatsQueryService($this->conn());

        $summary = $service->getCompletionWindowSummary(999999, 7);

        self::assertSame(0, $summary['rate'] ?? null);
        self::assertSame(0, $summary['completed'] ?? null);
        self::assertSame(0, $summary['scheduled'] ?? null);
        self::assertSame(0, $summary['days_analyzed'] ?? null);
    }

    public function testCompletionWindowSummaryReturnsZeroRateWithNoScheduledHabits(): void
    {
        $userId = $this->createUserAtOffsetDays(-7);
        $service = new StatsQueryService($this->conn());

        $summary = $service->getCompletionWindowSummary($userId, 7);

        self::assertSame(0, $summary['rate'] ?? null);
        self::assertSame(0, $summary['completed'] ?? null);
        self::assertSame(0, $summary['scheduled'] ?? null);
        self::assertGreaterThanOrEqual(1, (int) ($summary['days_analyzed'] ?? 0));
    }

    public function testCurrentStreakReturnsZeroWhenNoCompletionsExist(): void
    {
        $userId = $this->createUserAtOffsetDays(-5);
        $service = new StatsQueryService($this->conn());

        self::assertSame(0, $service->getCurrentStreak($userId));
    }

    public function testCurrentStreakCountsContiguousTodayYesterdayChain(): void
    {
        $userId = $this->createUserAtOffsetDays(-10);
        $habitId = $this->fixtures->createHabit($userId, ['frequency' => 'daily']);
        $this->setHabitCreatedAt($habitId, $this->shiftDate($this->today(), -10) . ' 08:00:00');
        $today = $this->today();
        $this->seedCompletionDates($habitId, $userId, [
            $today,
            $this->shiftDate($today, -1),
            $this->shiftDate($today, -2),
            $this->shiftDate($today, -5),
        ]);

        $service = new StatsQueryService($this->conn());

        self::assertSame(3, $service->getCurrentStreak($userId));
    }

    public function testCurrentStreakBreaksWhenThereIsGapGreaterThanOneDay(): void
    {
        $userId = $this->createUserAtOffsetDays(-10);
        $habitId = $this->fixtures->createHabit($userId, ['frequency' => 'daily']);
        $this->setHabitCreatedAt($habitId, $this->shiftDate($this->today(), -10) . ' 08:00:00');
        $today = $this->today();
        $this->seedCompletionDates($habitId, $userId, [
            $today,
            $this->shiftDate($today, -2),
        ]);

        $service = new StatsQueryService($this->conn());

        self::assertSame(1, $service->getCurrentStreak($userId));
    }

    public function testGetRecentHistoryClampsDaysByUserCreationDate(): void
    {
        $today = $this->today();
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $this->db()->execute("UPDATE users SET created_at = '" . $this->shiftDate($today, -2) . " 10:00:00' WHERE id = " . (int) $userId);
        $service = new StatsQueryService($this->conn());

        $rows = $service->getRecentHistory($userId, 10, $this->shiftDate($today, -2) . ' 10:00:00');

        self::assertCount(3, $rows);
        self::assertSame($today, $rows[0]['date'] ?? null);
        self::assertIsInt($rows[0]['completed'] ?? null);
        self::assertIsInt($rows[0]['total'] ?? null);
        self::assertIsFloat($rows[0]['percentage'] ?? null);
    }

    public function testGetTodayHabitsFiltersUnscheduledRows(): void
    {
        $userId = $this->createUserAtOffsetDays(-10);
        $today = $this->today();
        $todayWeekday = (int) date('w', strtotime($today));
        $otherWeekday = ($todayWeekday + 1) % 7;

        $dailyId = $this->fixtures->createHabit($userId, ['title' => 'Daily', 'frequency' => 'daily']);
        $weeklyTodayId = $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Today',
            'frequency' => 'weekly',
            'target_days' => json_encode([$todayWeekday]),
        ]);
        $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Other',
            'frequency' => 'weekly',
            'target_days' => json_encode([$otherWeekday]),
        ]);

        $service = new StatsQueryService($this->conn());
        $rows = $service->getTodayHabits($userId, $today);

        self::assertNotNull($this->findById($rows, $dailyId));
        self::assertNotNull($this->findById($rows, $weeklyTodayId));
        self::assertCount(2, $rows);
    }

    public function testGetMonthlyDataFillsMissingDaysWithZeroes(): void
    {
        $today = $this->today();
        $userId = $this->createUserAtOffsetDays(-10);
        $habitId = $this->fixtures->createHabit($userId, ['frequency' => 'daily']);
        $this->setHabitCreatedAt($habitId, $this->shiftDate($today, -10) . ' 08:00:00');
        $this->seedCompletionDates($habitId, $userId, [$today, $this->shiftDate($today, -2)]);

        $service = new StatsQueryService($this->conn());
        $data = $service->getMonthlyData($userId, 3);

        self::assertSame(3, count($data['labels'] ?? []));
        self::assertSame(3, count($data['completed'] ?? []));
        self::assertSame(3, count($data['total'] ?? []));
        self::assertSame([1, 0, 1], array_values($data['completed'] ?? []));
    }

    /**
     * @return array{0: StatsQueryService, 1: int, 2: int, 3: string}
     */
    private function createTrendFixture(): array
    {
        $today = $this->today();
        $userId = $this->createUserAtOffsetDays(-30);
        $habitId = $this->fixtures->createHabit($userId, ['frequency' => 'daily']);
        $this->setHabitCreatedAt($habitId, $this->shiftDate($today, -30) . ' 08:00:00');
        // Seed an older completion outside the compared windows so trend tests are not affected
        // by first-completion clamping of the previous window.
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => $this->shiftDate($today, -20)]);

        return [new StatsQueryService($this->conn()), $userId, $habitId, $today];
    }

    private function createUserAtOffsetDays(int $offsetDays): int
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $this->db()->execute(
            "UPDATE users SET created_at = '" . $this->shiftDate($this->today(), $offsetDays) . " 08:00:00' WHERE id = " . (int) $userId
        );

        return $userId;
    }

    private function setHabitCreatedAt(int $habitId, string $timestamp): void
    {
        $this->db()->execute("UPDATE habits SET created_at = '" . $timestamp . "' WHERE id = " . (int) $habitId);
    }

    /**
     * @param list<string> $dates
     */
    private function seedCompletionDates(int $habitId, int $userId, array $dates): void
    {
        foreach ($dates as $date) {
            $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => $date]);
        }
    }

    private function today(): string
    {
        return date('Y-m-d');
    }

    private function shiftDate(string $date, int $days): string
    {
        if ($days === 0) {
            return $date;
        }

        $modifier = ($days > 0 ? '+' : '') . $days . ' day';
        return date('Y-m-d', strtotime($date . ' ' . $modifier));
    }

    private function findById(array $rows, int $id): ?array
    {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }
}

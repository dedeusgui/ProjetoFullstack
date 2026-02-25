<?php

declare(strict_types=1);

namespace Tests\Action\Repository;

use App\Repository\StatsRepository;
use Tests\Support\ActionTestCase;

final class StatsRepositoryTest extends ActionTestCase
{
    public function testCoreCountAndFinderMethodsReturnExpectedValues(): void
    {
        $userId = $this->fixtures->createUser(['created_at' => '2026-02-01 08:00:00']);
        $habitId = $this->fixtures->createHabit($userId, [
            'start_date' => '2026-02-01',
        ]);
        $this->db()->execute('UPDATE habits SET current_streak = 2, longest_streak = 5, created_at = \'2026-02-01 09:00:00\' WHERE id = ' . (int) $habitId);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => '2026-02-10']);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => '2026-02-11']);
        $repository = new StatsRepository($this->conn());

        self::assertSame(1, $repository->countCompletedHabitsOnDate($userId, '2026-02-10'));
        self::assertNotNull($repository->findUserCreatedAt($userId));
        self::assertSame('2026-02-10', $repository->findFirstCompletionDate($userId));
        self::assertSame(2, $repository->countActiveDays($userId));
        self::assertSame(['2026-02-11', '2026-02-10'], $repository->findDistinctCompletionDatesDesc($userId));
        self::assertSame(5, $repository->findBestStreak($userId));
        self::assertSame(2, $repository->countTotalCompletions($userId));
    }

    public function testRangeAndAggregationQueriesReturnRepresentativeRows(): void
    {
        $userId = $this->fixtures->createUser(['created_at' => '2026-02-01 08:00:00']);
        $habit1 = $this->fixtures->createHabit($userId, [
            'title' => 'Habit 1',
            'category_id' => 10,
            'start_date' => '2026-02-01',
        ]);
        $habit2 = $this->fixtures->createHabit($userId, [
            'title' => 'Habit 2',
            'category_id' => 3,
            'start_date' => '2026-02-05',
        ]);
        $this->db()->execute('UPDATE habits SET created_at = \'2026-02-01 09:00:00\' WHERE id = ' . (int) $habit1);
        $this->db()->execute('UPDATE habits SET created_at = \'2026-02-05 09:00:00\' WHERE id = ' . (int) $habit2);
        $this->fixtures->createCompletion($habit1, $userId, ['completion_date' => '2026-02-10']);
        $this->fixtures->createCompletion($habit2, $userId, ['completion_date' => '2026-02-10']);
        $this->fixtures->createCompletion($habit1, $userId, ['completion_date' => '2026-02-11']);

        $repository = new StatsRepository($this->conn());
        $windowHabits = $repository->findHabitsForCompletionWindow($userId, '2026-02-01', '2026-02-12');
        $dailyCounts = $repository->findDailyCompletionCounts($userId, '2026-02-09', '2026-02-12');
        $categoryStats = $repository->findCategoryStats($userId);
        $history = $repository->findRecentHistory($userId, '2026-02-09', '2026-02-12');

        self::assertCount(2, $windowHabits);
        self::assertSame(3, $repository->countCompletedHabitOccurrencesInRange($userId, '2026-02-01', '2026-02-12'));
        self::assertSame([
            ['date' => '2026-02-10', 'completed' => 2],
            ['date' => '2026-02-11', 'completed' => 1],
        ], $dailyCounts);
        self::assertNotEmpty($categoryStats);
        self::assertArrayHasKey('category', $categoryStats[0]);
        self::assertArrayHasKey('percentage', $categoryStats[0]);
        self::assertCount(4, $history);
        self::assertSame('2026-02-12', (string) ($history[0]['date'] ?? ''));
        self::assertArrayHasKey('total', $history[0]);
        self::assertArrayHasKey('percentage', $history[0]);
    }
}

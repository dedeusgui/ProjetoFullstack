<?php

declare(strict_types=1);

namespace Tests\Action\Recommendation;

use App\Recommendation\BehaviorAnalyzer;
use Tests\Support\ActionTestCase;

final class BehaviorAnalyzerTest extends ActionTestCase
{
    public function testAnalyzeReturnsZeroedMetricsForUserWithoutHabits(): void
    {
        $userId = $this->fixtures->createUser();

        $result = BehaviorAnalyzer::analyze($this->conn(), $userId, '2026-02-10');

        self::assertSame(0, $result['total_habits'] ?? null);
        self::assertSame(0, $result['total_completions_all_time'] ?? null);
        self::assertSame(0, $result['current_streak'] ?? null);
        self::assertSame(0.0, (float) ($result['completion_rate_30'] ?? 1));
        self::assertSame(30, count($result['daily_series_30'] ?? []));
    }

    public function testAnalyzeReturnsRepresentativeMetricsForHabitAndCompletions(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'frequency' => 'daily',
            'start_date' => '2026-02-01',
        ]);
        $this->db()->execute('UPDATE habits SET current_streak = 3, longest_streak = 7 WHERE id = ' . (int) $habitId);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => '2026-02-08']);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => '2026-02-09']);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => '2026-02-10']);

        $result = BehaviorAnalyzer::analyze($this->conn(), $userId, '2026-02-10');

        self::assertSame(1, $result['total_habits'] ?? null);
        self::assertSame(3, $result['total_completions_all_time'] ?? null);
        self::assertSame(3, $result['current_streak'] ?? null);
        self::assertGreaterThan(0, (int) ($result['expected_7'] ?? 0));
        self::assertGreaterThan(0, (float) ($result['completion_rate_7'] ?? 0));
        self::assertArrayHasKey('consecutive_failures', $result);
        self::assertCount(30, $result['daily_series_30'] ?? []);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Action\Habits;

use App\Habits\HabitCompletionService;
use Tests\Support\ActionTestCase;

final class HabitCompletionServiceTest extends ActionTestCase
{
    public function testToggleCompletionAddsCompletionAndInvalidatesRecommendationSnapshot(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'frequency' => 'daily',
            'goal_type' => 'completion',
        ]);
        $this->insertRecommendation($userId);
        $service = new HabitCompletionService($this->conn());

        $result = $service->toggleCompletion($habitId, $userId, '2026-02-10', null, null, null);

        self::assertTrue($result['success'] ?? false);
        self::assertSame('1', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions WHERE habit_id = ' . (int) $habitId)['c'] ?? '0'));
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM user_recommendations WHERE user_id = ' . (int) $userId)['c'] ?? '1'));
    }

    public function testToggleCompletionRemovesExistingCompletionAndInvalidatesRecommendationSnapshot(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'frequency' => 'daily',
            'goal_type' => 'completion',
        ]);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => '2026-02-10']);
        $this->db()->execute('UPDATE habits SET total_completions = 1, current_streak = 1, longest_streak = 1 WHERE id = ' . (int) $habitId);
        $this->insertRecommendation($userId);
        $service = new HabitCompletionService($this->conn());

        $result = $service->toggleCompletion($habitId, $userId, '2026-02-10', null, null, null);

        self::assertTrue($result['success'] ?? false);
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions WHERE habit_id = ' . (int) $habitId)['c'] ?? '1'));
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM user_recommendations WHERE user_id = ' . (int) $userId)['c'] ?? '1'));
    }

    public function testToggleCompletionRejectsUnauthorizedHabitAccess(): void
    {
        $ownerId = $this->fixtures->createUser(['_counter' => 1]);
        $otherUserId = $this->fixtures->createUser(['_counter' => 2]);
        $habitId = $this->fixtures->createHabit($ownerId, ['frequency' => 'daily']);
        $service = new HabitCompletionService($this->conn());

        $result = $service->toggleCompletion($habitId, $otherUserId, '2026-02-10', null, null, null);

        self::assertFalse($result['success'] ?? true);
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions')['c'] ?? '1'));
    }

    public function testToggleCompletionRejectsUnscheduledDate(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'frequency' => 'weekly',
            'target_days' => json_encode([1]),
        ]);
        $service = new HabitCompletionService($this->conn());

        $result = $service->toggleCompletion($habitId, $userId, '2026-02-03', null, null, null);

        self::assertFalse($result['success'] ?? true);
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions')['c'] ?? '1'));
    }

    public function testToggleCompletionRejectsArchivedHabit(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'is_active' => 0,
            'archived_at' => '2026-02-01 10:00:00',
        ]);
        $service = new HabitCompletionService($this->conn());

        $result = $service->toggleCompletion($habitId, $userId, '2026-02-10', null, null, null);

        self::assertFalse($result['success'] ?? true);
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions')['c'] ?? '1'));
    }

    public function testToggleCompletionRequiresValueForQuantityGoalHabits(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'goal_type' => 'quantity',
            'goal_value' => 10,
            'goal_unit' => 'ml',
        ]);
        $service = new HabitCompletionService($this->conn());

        $result = $service->toggleCompletion($habitId, $userId, '2026-02-10', null, null, null);

        self::assertFalse($result['success'] ?? true);
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions')['c'] ?? '1'));
    }

    private function insertRecommendation(int $userId): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO user_recommendations (user_id, score, trend, risk_level, recommendation_text, recommendation_payload)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $score = 42;
        $trend = 'neutral';
        $risk = 'stable';
        $text = 'Keep going';
        $payload = json_encode(['source' => 'test']);
        $stmt->bind_param('iissss', $userId, $score, $trend, $risk, $text, $payload);
        $stmt->execute();
    }
}

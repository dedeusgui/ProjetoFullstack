<?php

declare(strict_types=1);

namespace Tests\Action\Config;

use Tests\Support\ActionTestCase;

final class AppHelpersIntegrationTest extends ActionTestCase
{
    public function testCategoryAndAchievementWrappersReturnRepresentativeData(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $habitId = $this->fixtures->createHabit($userId, ['is_active' => 1, 'current_streak' => 1, 'longest_streak' => 1]);
        $this->db()->execute('UPDATE habits SET current_streak = 1, longest_streak = 1 WHERE id = ' . (int) $habitId);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => date('Y-m-d')]);

        self::assertSame(10, getCategoryIdByName($this->conn(), 'Outros'));

        $dailyMap = getDailyCompletionsMap($this->conn(), $userId, 7);
        self::assertNotEmpty($dailyMap);
        self::assertGreaterThanOrEqual(1, getPerfectDaysStreak($this->conn(), $userId, 7));

        $achievements = getUserAchievements($this->conn(), $userId);
        self::assertNotEmpty($achievements);
        self::assertNotNull($this->findBySlug($achievements, 'first-step'));
    }

    public function testUserProgressWrappersPersistAndReturnSummary(): void
    {
        $userId = $this->fixtures->createUser();
        $achievements = [
            ['unlocked' => true, 'points' => 10, 'slug' => 'x'],
            ['unlocked' => true, 'points' => 230, 'slug' => 'y'],
            ['unlocked' => false, 'points' => 999, 'slug' => 'z'],
        ];

        persistUserProgress($this->conn(), $userId, 5, 500);
        $row = $this->db()->fetchOne('SELECT level, experience_points FROM users WHERE id = ' . (int) $userId);
        self::assertSame('5', (string) ($row['level'] ?? ''));
        self::assertSame('500', (string) ($row['experience_points'] ?? ''));

        $summary = getUserProgressSummary($this->conn(), $userId, $achievements);
        self::assertSame(240, $summary['total_xp'] ?? null);
        self::assertSame(2, $summary['level'] ?? null);
        self::assertSame(2, $summary['unlocked_achievements_count'] ?? null);
    }

    private function findBySlug(array $items, string $slug): ?array
    {
        foreach ($items as $item) {
            if (($item['slug'] ?? null) === $slug) {
                return $item;
            }
        }

        return null;
    }
}

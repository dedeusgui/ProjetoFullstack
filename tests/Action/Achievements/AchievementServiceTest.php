<?php

declare(strict_types=1);

namespace Tests\Action\Achievements;

use App\Achievements\AchievementService;
use Tests\Support\ActionTestCase;

final class AchievementServiceTest extends ActionTestCase
{
    public function testSyncUserAchievementsUnlocksFirstStepForFirstCompletion(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $habitId = $this->fixtures->createHabit($userId, [
            'is_active' => 1,
            'longest_streak' => 1,
            'current_streak' => 1,
        ]);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => date('Y-m-d')]);
        $service = new AchievementService($this->conn());

        $achievements = $service->syncUserAchievements($userId);

        self::assertNotEmpty($achievements);
        $firstStep = $this->findBySlug($achievements, 'first-step');
        self::assertNotNull($firstStep);
        self::assertTrue((bool) ($firstStep['unlocked'] ?? false));
        self::assertGreaterThanOrEqual(100, (int) ($firstStep['progress_percent'] ?? 0));
        self::assertSame('bi bi-flag-fill', $firstStep['icon'] ?? null);

        $persisted = $this->db()->fetchOne('SELECT COUNT(*) AS c FROM user_achievements WHERE user_id = ' . (int) $userId);
        self::assertSame('1', (string) ($persisted['c'] ?? '0'));
    }

    public function testGetDailyCompletionsMapAndPerfectDaysStreakReturnExpectedValues(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $habitId = $this->fixtures->createHabit($userId, ['is_active' => 1]);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => $yesterday]);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => $today]);
        $service = new AchievementService($this->conn());

        $map = $service->getDailyCompletionsMap($userId, 7);
        $perfect = $service->getPerfectDaysStreak($userId, 7);

        self::assertSame(1, $map[$today] ?? null);
        self::assertSame(1, $map[$yesterday] ?? null);
        self::assertGreaterThanOrEqual(2, $perfect);
    }

    public function testMapIconToBootstrapHandlesKnownAndFallbackValues(): void
    {
        self::assertSame('bi bi-fire', AchievementService::mapIconToBootstrap('fire'));
        self::assertSame('bi bi-bell', AchievementService::mapIconToBootstrap('bi-bell'));
        self::assertSame('bi bi-patch-check-fill', AchievementService::mapIconToBootstrap(''));
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

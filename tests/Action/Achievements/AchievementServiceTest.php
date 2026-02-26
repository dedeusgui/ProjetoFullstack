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

        $persisted = $this->db()->fetchOne('SELECT COUNT(*) AS c FROM user_achievement_unlocks WHERE user_id = ' . (int) $userId);
        self::assertSame('1', (string) ($persisted['c'] ?? '0'));
    }

    public function testGetDailyCompletionsMapAndPerfectDaysStreakReturnExpectedValues(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $habitId = $this->fixtures->createHabit($userId, ['is_active' => 1]);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
        $this->db()->execute("UPDATE habits SET created_at = '" . $yesterday . " 00:00:00', start_date = '" . $yesterday . "' WHERE id = " . (int) $habitId);
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

    public function testGetAchievementsPageDataReturnsRecentUnlockedTimelineLimitedAndOrdered(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $achievementRows = $this->db()->fetchAll('SELECT id FROM achievement_definitions WHERE is_active = 1 ORDER BY id ASC LIMIT 6');
        self::assertCount(6, $achievementRows, 'Expected at least 6 active achievements in seeded schema.');

        $stmt = $this->db()->prepare('INSERT INTO user_achievement_unlocks (user_id, achievement_definition_id, unlocked_at, awarded_points, rule_version, source) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($achievementRows as $index => $achievementRow) {
            $achievementId = (int) ($achievementRow['id'] ?? 0);
            $unlockedAt = sprintf('2026-02-%02d 10:00:00', $index + 1);
            $points = 100;
            $ruleVersion = 1;
            $source = 'test_seed';
            $stmt->bind_param('iisiis', $userId, $achievementId, $unlockedAt, $points, $ruleVersion, $source);
            $stmt->execute();
        }

        $service = new AchievementService($this->conn());
        $pageData = $service->getAchievementsPageData($userId);
        $recentUnlocked = $pageData['recent_unlocked'] ?? [];

        self::assertCount(5, $recentUnlocked);

        $expectedIds = array_slice(
            array_reverse(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $achievementRows)),
            0,
            5
        );
        $recentIds = array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $recentUnlocked);
        self::assertSame($expectedIds, $recentIds);

        $recentTimestamps = array_map(
            static fn(array $item): int => strtotime((string) ($item['date'] ?? '1970-01-01 00:00:00')) ?: 0,
            $recentUnlocked
        );
        $expectedTimestamps = $recentTimestamps;
        rsort($expectedTimestamps);
        self::assertSame($expectedTimestamps, $recentTimestamps);

        foreach ($recentUnlocked as $item) {
            self::assertTrue((bool) ($item['unlocked'] ?? false));
            self::assertNotSame('', (string) ($item['date'] ?? ''));
        }
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

<?php

declare(strict_types=1);

namespace Tests\Action\UserProgress;

use App\UserProgress\UserProgressService;
use Tests\Support\ActionTestCase;

final class UserProgressServiceTest extends ActionTestCase
{
    public function testCalculateLevelFromXpHandlesBoundaries(): void
    {
        $service = new UserProgressService($this->conn());

        self::assertSame(1, $service->calculateLevelFromXp(0));
        self::assertSame(2, $service->calculateLevelFromXp(100));
        self::assertSame(3, $service->calculateLevelFromXp(200));
        self::assertSame(5, $service->calculateLevelFromXp(480));
    }

    public function testRefreshUserProgressSummaryBuildsSummaryAndPersistsLevelAndXp(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new UserProgressService($this->conn());
        $achievements = [
            ['unlocked' => true, 'points' => 10, 'slug' => 'a'],
            ['unlocked' => false, 'points' => 999, 'slug' => 'b'],
            ['unlocked' => true, 'points' => 230, 'slug' => 'c'],
        ];

        $summary = $service->refreshUserProgressSummary($userId, $achievements);

        self::assertSame(240, $summary['total_xp'] ?? null);
        self::assertSame(3, $summary['level'] ?? null);
        self::assertSame(2, $summary['unlocked_achievements_count'] ?? null);
        self::assertSame(3, $summary['achievements_count'] ?? null);
        self::assertGreaterThanOrEqual(0, (int) ($summary['xp_progress_percent'] ?? -1));
        self::assertLessThanOrEqual(100, (int) ($summary['xp_progress_percent'] ?? 101));
        self::assertArrayHasKey('next_level_title', $summary);
        self::assertArrayHasKey('profile_badges', $summary);
        self::assertArrayHasKey('total_badges_unlocked', $summary);

        $row = $this->db()->fetchOne('SELECT level, experience_points FROM users WHERE id = ' . (int) $userId);
        self::assertSame((string) ($summary['level'] ?? ''), (string) ($row['level'] ?? ''));
        self::assertSame((string) ($summary['total_xp'] ?? ''), (string) ($row['experience_points'] ?? ''));
    }

    public function testGetUserProgressSummaryDelegatesToRefreshMethod(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new UserProgressService($this->conn());
        $achievements = [
            ['unlocked' => true, 'points' => 10],
        ];

        $summary = $service->getUserProgressSummary($userId, $achievements);

        self::assertSame(10, $summary['total_xp'] ?? null);
        self::assertSame(1, $summary['unlocked_achievements_count'] ?? null);
    }
}

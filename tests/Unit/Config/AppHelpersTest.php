<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

final class AppHelpersTest extends TestCase
{
    public function testPureCompatibilityWrappersDelegateToUnderlyingLogic(): void
    {
        self::assertSame('morning', mapTimeOfDay('Manhã'));
        self::assertSame('Tarde', mapTimeOfDayReverse('afternoon'));
        self::assertSame('10/02/2026', formatDateBr('2026-02-10'));
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', getAppToday());
        self::assertSame([1, 3], normalizeTargetDays('[1,3,9]'));
    }

    public function testHabitScheduleWrappersWorkForRepresentativeDailyHabit(): void
    {
        $habit = [
            'frequency' => 'daily',
            'target_days' => null,
            'start_date' => '2026-02-01',
            'end_date' => null,
        ];

        self::assertTrue(isHabitScheduledForDate($habit, '2026-02-10'));
        self::assertSame('2026-02-10', getNextHabitDueDate($habit, '2026-02-10'));
    }

    public function testCompatibilityWrappersForAchievementAndProgressHelpers(): void
    {
        self::assertSame('bi bi-fire', mapAchievementIconToBootstrap('fire'));
        self::assertSame(3, calculateLevelFromXp(480));
    }
}

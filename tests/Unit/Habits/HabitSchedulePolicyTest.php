<?php

declare(strict_types=1);

namespace Tests\Unit\Habits;

use App\Habits\HabitSchedulePolicy;
use PHPUnit\Framework\TestCase;

final class HabitSchedulePolicyTest extends TestCase
{
    public function testNormalizeTargetDaysFiltersInvalidValuesAndDuplicates(): void
    {
        $result = HabitSchedulePolicy::normalizeTargetDays(json_encode([1, 1, 2, 7, -1, '3']));

        self::assertSame([1, 2, 3], $result);
    }

    public function testNormalizeTargetDaysReturnsEmptyOnInvalidJson(): void
    {
        self::assertSame([], HabitSchedulePolicy::normalizeTargetDays('not-json'));
    }

    public function testIsScheduledForDateHonorsStartAndEndDate(): void
    {
        $habit = [
            'frequency' => 'daily',
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-12',
        ];

        self::assertFalse(HabitSchedulePolicy::isScheduledForDate($habit, '2026-02-09'));
        self::assertTrue(HabitSchedulePolicy::isScheduledForDate($habit, '2026-02-10'));
        self::assertTrue(HabitSchedulePolicy::isScheduledForDate($habit, '2026-02-12'));
        self::assertFalse(HabitSchedulePolicy::isScheduledForDate($habit, '2026-02-13'));
    }

    public function testWeeklyScheduleFallsBackToStartDateWeekdayWhenTargetDaysMissing(): void
    {
        $habit = [
            'frequency' => 'weekly',
            'start_date' => '2026-02-02',
            'target_days' => null,
        ];

        self::assertTrue(HabitSchedulePolicy::isScheduledForDate($habit, '2026-02-09'));
        self::assertFalse(HabitSchedulePolicy::isScheduledForDate($habit, '2026-02-10'));
    }

    public function testCustomScheduleRequiresTargetDays(): void
    {
        $habit = [
            'frequency' => 'custom',
            'target_days' => null,
        ];

        self::assertFalse(HabitSchedulePolicy::isScheduledForDate($habit, '2026-02-10'));
    }

    public function testGetNextDueDateFindsNextMatchingCustomDay(): void
    {
        $habit = [
            'frequency' => 'custom',
            'target_days' => json_encode([1]),
        ];

        $nextDate = HabitSchedulePolicy::getNextDueDate($habit, '2026-02-03');

        self::assertSame('2026-02-09', $nextDate);
    }

    public function testGetNextDueDateReturnsNullForInvalidStartDate(): void
    {
        $habit = ['frequency' => 'daily'];

        self::assertNull(HabitSchedulePolicy::getNextDueDate($habit, 'invalid-date'));
    }
}

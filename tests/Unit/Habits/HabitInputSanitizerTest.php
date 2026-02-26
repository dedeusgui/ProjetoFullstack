<?php

declare(strict_types=1);

namespace Tests\Unit\Habits;

use App\Habits\HabitInputSanitizer;
use PHPUnit\Framework\TestCase;

final class HabitInputSanitizerTest extends TestCase
{
    public function testSanitizesDefaultsAndNormalizesInvalidOptions(): void
    {
        $result = HabitInputSanitizer::fromRequest([
            'title' => '  Walk  ',
            'description' => '  Daily walk  ',
            'category' => 'Outros',
            'time' => 'Tarde',
            'color' => 'blue',
            'frequency' => 'invalid',
            'goal_type' => 'invalid',
            'goal_value' => 0,
        ]);

        self::assertSame([], $result['errors']);
        self::assertSame('Walk', $result['data']['title']);
        self::assertSame('Daily walk', $result['data']['description']);
        self::assertSame('#4a74ff', $result['data']['color']);
        self::assertSame('daily', $result['data']['frequency']);
        self::assertSame('completion', $result['data']['goal_type']);
        self::assertSame(1, $result['data']['goal_value']);
    }

    public function testWeeklyRequiresAtLeastOneTargetDayAndCustomFallsBackToDaily(): void
    {
        $weekly = HabitInputSanitizer::fromRequest([
            'title' => 'Run',
            'category' => 'Outros',
            'time' => 'Noite',
            'frequency' => 'weekly',
            'target_days' => [],
        ]);

        $custom = HabitInputSanitizer::fromRequest([
            'title' => 'Run',
            'category' => 'Outros',
            'time' => 'Noite',
            'frequency' => 'custom',
            'target_days' => [],
        ]);

        self::assertNotEmpty($weekly['errors']);
        self::assertSame('daily', $custom['data']['frequency']);
        self::assertSame([], $custom['errors']);
    }

    public function testTargetDaysAreDeduplicatedAndFiltered(): void
    {
        $result = HabitInputSanitizer::fromRequest([
            'title' => 'Study',
            'category' => 'Outros',
            'time' => 'Tarde',
            'frequency' => 'weekly',
            'target_days' => [1, '2', 2, 7, -1, 0],
        ]);

        self::assertSame(json_encode([1, 2, 0]), $result['data']['target_days_json']);
        self::assertSame([], $result['errors']);
    }

    public function testMissingRequiredFieldsProduceErrors(): void
    {
        $result = HabitInputSanitizer::fromRequest([]);

        self::assertGreaterThanOrEqual(3, count($result['errors']));
    }
}

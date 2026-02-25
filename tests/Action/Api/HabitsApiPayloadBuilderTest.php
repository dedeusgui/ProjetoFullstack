<?php

declare(strict_types=1);

namespace Tests\Action\Api;

use App\Api\Internal\HabitsApiPayloadBuilder;
use Tests\Support\ActionTestCase;

final class HabitsApiPayloadBuilderTest extends ActionTestCase
{
    public function testAllScopeMapsBasePayloadAndDefaults(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $habitId = $this->fixtures->createHabit($userId, [
            'title' => 'Null Defaults Habit',
            'goal_unit' => null,
        ]);
        $this->db()->execute('UPDATE habits SET category_id = NULL, time_of_day = NULL, goal_value = NULL WHERE id = ' . (int) $habitId);

        $payload = HabitsApiPayloadBuilder::build($this->conn(), $userId, 'all');

        self::assertTrue($payload['success'] ?? false);
        self::assertSame('all', $payload['scope'] ?? null);
        self::assertArrayHasKey('generated_at', $payload);
        self::assertArrayHasKey('data', $payload);
        self::assertArrayHasKey('count', $payload['data']);
        self::assertArrayHasKey('habits', $payload['data']);

        $habit = $this->findById($payload['data']['habits'] ?? [], $habitId);
        self::assertNotNull($habit);
        self::assertSame('Sem categoria', $habit['category'] ?? null);
        self::assertSame('Qualquer', $habit['time'] ?? null);
        self::assertSame(1, $habit['goal_value'] ?? null);
        self::assertSame('', $habit['goal_unit'] ?? null);
        self::assertArrayHasKey('target_days', $habit);
    }

    public function testTodayScopeFiltersUnscheduledHabits(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $today = $this->today();
        $todayWeekday = (int) date('w', strtotime($today));
        $otherWeekday = ($todayWeekday + 1) % 7;

        $dailyHabitId = $this->fixtures->createHabit($userId, ['title' => 'Daily Habit', 'frequency' => 'daily']);
        $todayHabitId = $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Today',
            'frequency' => 'weekly',
            'target_days' => json_encode([$todayWeekday]),
        ]);
        $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Other',
            'frequency' => 'weekly',
            'target_days' => json_encode([$otherWeekday]),
        ]);

        $payload = HabitsApiPayloadBuilder::build($this->conn(), $userId, 'today');

        self::assertSame('today', $payload['scope'] ?? null);
        $habits = $payload['data']['habits'] ?? [];
        self::assertCount(2, $habits);
        self::assertNotNull($this->findById($habits, $dailyHabitId));
        self::assertNotNull($this->findById($habits, $todayHabitId));
    }

    public function testPageScopeReturnsGroupedHabitsStatsAndArchiveData(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $today = $this->today();
        $todayWeekday = (int) date('w', strtotime($today));
        $otherWeekday = ($todayWeekday + 1) % 7;

        $dailyHabitId = $this->fixtures->createHabit($userId, ['title' => 'Daily Page Habit', 'frequency' => 'daily']);
        $weeklyTodayId = $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Today Page',
            'frequency' => 'weekly',
            'target_days' => json_encode([$todayWeekday]),
        ]);
        $weeklyOtherId = $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Other Page',
            'frequency' => 'weekly',
            'target_days' => json_encode([$otherWeekday]),
        ]);
        $archivedHabitId = $this->fixtures->createHabit($userId, [
            'title' => 'Archived Habit',
            'is_active' => 0,
            'archived_at' => $today . ' 10:00:00',
        ]);

        $this->fixtures->createCompletion($dailyHabitId, $userId, ['completion_date' => $today]);

        $payload = HabitsApiPayloadBuilder::build($this->conn(), $userId, 'page');

        self::assertSame('page', $payload['scope'] ?? null);
        $data = $payload['data'] ?? [];
        self::assertSame($today, $data['today_date'] ?? null);
        self::assertSame(3, (int) (($data['stats']['total_habits'] ?? -1)));
        self::assertSame(1, (int) (($data['stats']['archived_habits'] ?? -1)));
        self::assertArrayHasKey('habits_by_week_day', $data);
        self::assertCount(7, $data['habits_by_week_day']);
        self::assertArrayHasKey('categories', $data);

        $todayHabits = $data['habits'] ?? [];
        self::assertNotNull($this->findById($todayHabits, $dailyHabitId));
        self::assertNotNull($this->findById($todayHabits, $weeklyTodayId));
        self::assertNull($this->findById($todayHabits, $weeklyOtherId));

        $dailyPayload = $this->findById($todayHabits, $dailyHabitId);
        self::assertNotNull($dailyPayload);
        self::assertFalse($dailyPayload['can_complete_today'] ?? true);
        self::assertSame(date('Y-m-d', strtotime($today . ' +1 day')), $dailyPayload['next_due_date'] ?? null);

        $archivedPayload = $this->findById($data['archived_habits'] ?? [], $archivedHabitId);
        self::assertNotNull($archivedPayload);
        self::assertArrayHasKey('archived_at', $archivedPayload);

        $byDay = $data['habits_by_week_day'];
        foreach (range(0, 6) as $weekDay) {
            self::assertArrayHasKey($weekDay, $byDay);
            self::assertArrayHasKey('label', $byDay[$weekDay]);
            self::assertArrayHasKey('habits', $byDay[$weekDay]);
            self::assertNotNull($this->findById($byDay[$weekDay]['habits'], $dailyHabitId));
        }

        self::assertNotNull($this->findById($byDay[$todayWeekday]['habits'], $weeklyTodayId));
        self::assertNull($this->findById($byDay[$otherWeekday]['habits'], $weeklyTodayId));
        self::assertNotNull($this->findById($byDay[$otherWeekday]['habits'], $weeklyOtherId));
    }

    public function testInvalidScopeFallsBackToAll(): void
    {
        $userId = $this->fixtures->createUser();

        $payload = HabitsApiPayloadBuilder::build($this->conn(), $userId, 'bad-scope');

        self::assertSame('all', $payload['scope'] ?? null);
    }

    private function today(): string
    {
        return date('Y-m-d');
    }

    private function findById(array $rows, int $id): ?array
    {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }
}

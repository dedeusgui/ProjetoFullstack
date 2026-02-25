<?php

declare(strict_types=1);

namespace Tests\Action\Habits;

use App\Habits\HabitQueryService;
use Tests\Support\ActionTestCase;

final class HabitQueryServiceTest extends ActionTestCase
{
    public function testGetTodayHabitsFiltersUnscheduledHabits(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $today = $this->today();
        $todayWeekday = (int) date('w', strtotime($today));
        $otherWeekday = ($todayWeekday + 1) % 7;

        $dailyId = $this->fixtures->createHabit($userId, ['title' => 'Daily Habit', 'frequency' => 'daily']);
        $weeklyTodayId = $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Today Habit',
            'frequency' => 'weekly',
            'target_days' => json_encode([$todayWeekday]),
        ]);
        $this->fixtures->createHabit($userId, [
            'title' => 'Weekly Other Habit',
            'frequency' => 'weekly',
            'target_days' => json_encode([$otherWeekday]),
        ]);

        $service = new HabitQueryService($this->conn());
        $rows = $service->getTodayHabits($userId, $today);

        self::assertCount(2, $rows);
        self::assertNotNull($this->findById($rows, $dailyId));
        self::assertNotNull($this->findById($rows, $weeklyTodayId));
    }

    public function testGetUserHabitsIncludesCompletedTodayFlag(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $today = $this->today();
        $habitId = $this->fixtures->createHabit($userId, ['title' => 'Completed Habit']);
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => $today]);

        $service = new HabitQueryService($this->conn());
        $rows = $service->getUserHabits($userId);

        $habit = $this->findById($rows, $habitId);
        self::assertNotNull($habit);
        self::assertTrue((bool) ($habit['completed_today'] ?? false));
    }

    public function testGetArchivedHabitsReturnsArchivedRowsOnlyAndCountsMatch(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $today = $this->today();
        $activeId = $this->fixtures->createHabit($userId, ['title' => 'Active Habit']);
        $archivedId = $this->fixtures->createHabit($userId, [
            'title' => 'Archived Habit',
            'is_active' => 0,
            'archived_at' => $today . ' 09:00:00',
        ]);

        $service = new HabitQueryService($this->conn());
        $archived = $service->getArchivedHabits($userId);

        self::assertCount(1, $archived);
        self::assertNull($this->findById($archived, $activeId));
        self::assertNotNull($this->findById($archived, $archivedId));
        self::assertSame(1, $service->getArchivedHabitsCount($userId));
        self::assertSame(1, $service->getTotalHabits($userId));
    }

    public function testGetAllCategoriesReturnsAlphabeticallyOrderedRows(): void
    {
        $service = new HabitQueryService($this->conn());

        $categories = $service->getAllCategories();

        self::assertNotEmpty($categories);
        self::assertArrayHasKey('id', $categories[0]);
        self::assertArrayHasKey('name', $categories[0]);

        if (count($categories) >= 2) {
            self::assertLessThanOrEqual(
                0,
                strcmp(mb_strtolower((string) $categories[0]['name']), mb_strtolower((string) $categories[1]['name']))
            );
        }
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

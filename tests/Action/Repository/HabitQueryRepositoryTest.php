<?php

declare(strict_types=1);

namespace Tests\Action\Repository;

use App\Repository\HabitQueryRepository;
use Tests\Support\ActionTestCase;

final class HabitQueryRepositoryTest extends ActionTestCase
{
    public function testFindActiveAndArchivedHabitsIncludeCompletionFlagsAndCounts(): void
    {
        $userId = $this->fixtures->createUser();
        $today = date('Y-m-d');
        $activeId = $this->fixtures->createHabit($userId, ['title' => 'Active Habit']);
        $archivedId = $this->fixtures->createHabit($userId, [
            'title' => 'Archived Habit',
            'is_active' => 0,
            'archived_at' => $today . ' 10:00:00',
        ]);
        $this->fixtures->createCompletion($activeId, $userId, ['completion_date' => $today]);
        $repository = new HabitQueryRepository($this->conn());

        $active = $repository->findActiveHabitsWithCompletionOnDate($userId, $today);
        $archived = $repository->findArchivedHabitsWithCompletionOnDate($userId, $today);

        self::assertSame(1, $repository->countActiveHabits($userId));
        self::assertSame(1, $repository->countArchivedHabits($userId));
        self::assertNotNull($this->findById($active, $activeId));
        self::assertNotNull($this->findById($archived, $archivedId));
        self::assertSame('1', (string) ($this->findById($active, $activeId)['completed_today'] ?? '0'));
    }

    public function testFindActiveHabitsOrderedForDayAndFindAllCategories(): void
    {
        $userId = $this->fixtures->createUser();
        $today = date('Y-m-d');
        $firstId = $this->fixtures->createHabit($userId, ['title' => 'Zeta', 'time_of_day' => 'evening']);
        $secondId = $this->fixtures->createHabit($userId, ['title' => 'Alpha', 'time_of_day' => 'morning']);
        $repository = new HabitQueryRepository($this->conn());

        $rows = $repository->findActiveHabitsOrderedForDay($userId, $today);
        $ids = array_map(static fn(array $row): int => (int) $row['id'], $rows);

        self::assertSame([$secondId, $firstId], $ids);

        $categories = $repository->findAllCategories();
        self::assertNotEmpty($categories);
        self::assertSame('Outros', $this->findCategoryById($categories, 10)['name'] ?? null);
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

    private function findCategoryById(array $rows, int $id): ?array
    {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }
}

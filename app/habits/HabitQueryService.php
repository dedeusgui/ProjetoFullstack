<?php

namespace App\Habits;

use App\Repository\HabitQueryRepository;
use App\Support\UserLocalDateResolver;

class HabitQueryService
{
    private \mysqli $conn;
    private HabitQueryRepository $habitQueryRepository;
    private UserLocalDateResolver $userLocalDateResolver;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->habitQueryRepository = new HabitQueryRepository($conn);
        $this->userLocalDateResolver = new UserLocalDateResolver($conn);
    }

    public function getUserTodayDate(int $userId): string
    {
        return $this->userLocalDateResolver->getTodayDateForUser($userId);
    }

    public function getUserHabits(int $userId): array
    {
        return $this->habitQueryRepository->findActiveHabitsWithCompletionOnDate($userId, $this->getUserTodayDate($userId));
    }

    public function getArchivedHabits(int $userId): array
    {
        return $this->habitQueryRepository->findArchivedHabitsWithCompletionOnDate($userId, $this->getUserTodayDate($userId));
    }

    public function getTodayHabits(int $userId, ?string $targetDate = null): array
    {
        $date = $targetDate ?? $this->getUserTodayDate($userId);
        $rows = $this->habitQueryRepository->findActiveHabitsOrderedForDay($userId, $date);

        return array_values(array_filter($rows, static fn(array $habit): bool => HabitSchedulePolicy::isScheduledForDate($habit, $date)));
    }

    public function getAllCategories(): array
    {
        return $this->habitQueryRepository->findAllCategories();
    }

    public function getTotalHabits(int $userId): int
    {
        return $this->habitQueryRepository->countActiveHabits($userId);
    }

    public function getArchivedHabitsCount(int $userId): int
    {
        return $this->habitQueryRepository->countArchivedHabits($userId);
    }
}

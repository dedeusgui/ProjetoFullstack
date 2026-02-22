<?php

namespace App\Habits;

class HabitQueryService
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getUserHabits(int $userId): array
    {
        return getUserHabits($this->conn, $userId);
    }

    public function getArchivedHabits(int $userId): array
    {
        return getArchivedHabits($this->conn, $userId);
    }

    public function getTodayHabits(int $userId, ?string $targetDate = null): array
    {
        return getTodayHabits($this->conn, $userId, $targetDate);
    }

    public function getAllCategories(): array
    {
        return getAllCategories($this->conn);
    }

    public function getTotalHabits(int $userId): int
    {
        return (int) getTotalHabits($this->conn, $userId);
    }

    public function getArchivedHabitsCount(int $userId): int
    {
        return (int) getArchivedHabitsCount($this->conn, $userId);
    }
}

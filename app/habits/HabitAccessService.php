<?php
namespace App\Habits;

use App\Repository\HabitRepository;

class HabitAccessService
{
    private HabitRepository $habitRepository;

    public function __construct(\mysqli $conn)
    {
        $this->habitRepository = new HabitRepository($conn);
    }

    public function userOwnsHabit(int $habitId, int $userId): bool
    {
        return $this->habitRepository->userOwnsHabit($habitId, $userId);
    }
}

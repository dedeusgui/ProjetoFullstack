<?php

class HabitAccessService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function userOwnsHabit(int $habitId, int $userId): bool
    {
        $stmt = $this->conn->prepare('SELECT id FROM habits WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->bind_param('ii', $habitId, $userId);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }
}

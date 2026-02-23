<?php

namespace App\Repository;

class HabitQueryRepository
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findActiveHabitsWithCompletionOnDate(int $userId, string $date): array
    {
        $sql = "
            SELECT
                h.*,
                c.name AS category_name,
                c.color AS category_color,
                EXISTS(
                    SELECT 1 FROM habit_completions
                    WHERE habit_id = h.id
                      AND completion_date = ?
                      AND user_id = h.user_id
                ) AS completed_today
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            WHERE h.user_id = ? AND h.is_active = 1 AND h.archived_at IS NULL
            ORDER BY h.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $date, $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findArchivedHabitsWithCompletionOnDate(int $userId, string $date): array
    {
        $sql = "
            SELECT
                h.*,
                c.name AS category_name,
                c.color AS category_color,
                EXISTS(
                    SELECT 1 FROM habit_completions
                    WHERE habit_id = h.id
                      AND completion_date = ?
                      AND user_id = h.user_id
                ) AS completed_today
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            WHERE h.user_id = ? AND h.archived_at IS NOT NULL
            ORDER BY h.archived_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $date, $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findActiveHabitsOrderedForDay(int $userId, string $date): array
    {
        $sql = "
            SELECT
                h.*,
                c.name AS category_name,
                EXISTS(
                    SELECT 1 FROM habit_completions
                    WHERE habit_id = h.id
                      AND completion_date = ?
                      AND user_id = h.user_id
                ) AS completed_today
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            WHERE h.user_id = ? AND h.is_active = 1 AND h.archived_at IS NULL
            ORDER BY h.time_of_day, h.title
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $date, $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countActiveHabits(int $userId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM habits WHERE user_id = ? AND is_active = 1 AND archived_at IS NULL');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function countArchivedHabits(int $userId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM habits WHERE user_id = ? AND archived_at IS NOT NULL');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function findAllCategories(): array
    {
        $result = $this->conn->query('SELECT * FROM categories ORDER BY name ASC');
        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

<?php

namespace App\Repository;

class HabitRepository
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
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

    public function createForUser(int $userId, int $categoryId, array $data): bool
    {
        $stmt = $this->conn->prepare("INSERT INTO habits (
            user_id, category_id, title, description, icon, color,
            frequency, target_days, time_of_day, goal_type, goal_value, goal_unit,
            start_date, is_active, archived_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 1, NULL)");

        $stmt->bind_param(
            'iissssssssis',
            $userId,
            $categoryId,
            $data['title'],
            $data['description'],
            $data['icon'],
            $data['color'],
            $data['frequency'],
            $data['target_days_json'],
            $data['time_of_day_db'],
            $data['goal_type'],
            $data['goal_value'],
            $data['goal_unit']
        );

        return $stmt->execute();
    }

    public function updateForUser(int $habitId, int $userId, int $categoryId, array $data): bool
    {
        $stmt = $this->conn->prepare("UPDATE habits SET
            category_id = ?,
            title = ?,
            description = ?,
            icon = ?,
            color = ?,
            frequency = ?,
            target_days = ?,
            time_of_day = ?,
            goal_type = ?,
            goal_value = ?,
            goal_unit = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?");

        $stmt->bind_param(
            'issssssssisii',
            $categoryId,
            $data['title'],
            $data['description'],
            $data['icon'],
            $data['color'],
            $data['frequency'],
            $data['target_days_json'],
            $data['time_of_day_db'],
            $data['goal_type'],
            $data['goal_value'],
            $data['goal_unit'],
            $habitId,
            $userId
        );

        return $stmt->execute();
    }

    public function deleteForUser(int $habitId, int $userId): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM habits WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $habitId, $userId);
        return $stmt->execute();
    }

    public function archiveForUser(int $habitId, int $userId): bool
    {
        $stmt = $this->conn->prepare('UPDATE habits SET archived_at = CURRENT_TIMESTAMP, is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $habitId, $userId);
        return $stmt->execute();
    }

    public function restoreForUser(int $habitId, int $userId): bool
    {
        $stmt = $this->conn->prepare('UPDATE habits SET archived_at = NULL, is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $habitId, $userId);
        return $stmt->execute();
    }
}

<?php

namespace App\Repository;

class AchievementRepository
{
    use InteractsWithDatabase;

    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findActiveWithUserUnlockData(int $userId): array
    {
        $stmt = $this->prepareOrFail(
            "SELECT
                a.id,
                a.slug,
                a.name,
                a.description,
                a.icon,
                a.badge_color,
                a.criteria_type,
                a.criteria_value,
                a.points,
                a.rarity,
                ua.id AS user_achievement_id,
                ua.unlocked_at,
                ua.progress AS stored_progress
            FROM achievements a
            LEFT JOIN user_achievements ua
                ON a.id = ua.achievement_id
               AND ua.user_id = ?
            WHERE a.is_active = 1
            ORDER BY FIELD(a.rarity, 'legendary', 'epic', 'rare', 'common'), a.criteria_value ASC, a.id ASC"
        );

        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        $result = $this->getResultOrFail($stmt);
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getUserLevelAndXp(int $userId): array
    {
        $stmt = $this->prepareOrFail('SELECT name, level, experience_points FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_assoc() ?: [];
    }

    public function countActiveAchievements(): int
    {
        $result = $this->conn->query('SELECT COUNT(*) AS total FROM achievements WHERE is_active = 1');
        $row = $result ? $result->fetch_assoc() : [];

        return (int) ($row['total'] ?? 0);
    }

    public function countUnlockedAchievements(int $userId): int
    {
        $stmt = $this->prepareOrFail('SELECT COUNT(*) AS total FROM user_achievements WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];
        return (int) ($row['total'] ?? 0);
    }
}

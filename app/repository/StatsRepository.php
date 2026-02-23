<?php

namespace App\Repository;

class StatsRepository
{
    use InteractsWithDatabase;

    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function countCompletedHabitsOnDate(int $userId, string $date): int
    {
        $stmt = $this->prepareOrFail("
            SELECT COUNT(DISTINCT hc.habit_id) AS total
            FROM habit_completions hc
            INNER JOIN habits h ON hc.habit_id = h.id
            WHERE hc.user_id = ?
              AND hc.completion_date = ?
              AND h.is_active = 1
        ");
        $stmt->bind_param('is', $userId, $date);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function findUserCreatedAt(int $userId): ?string
    {
        $stmt = $this->prepareOrFail('SELECT created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc();

        return $row['created_at'] ?? null;
    }

    public function findFirstCompletionDate(int $userId): ?string
    {
        $stmt = $this->prepareOrFail('SELECT MIN(completion_date) AS first_completion_date FROM habit_completions WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return $row['first_completion_date'] ?? null;
    }

    public function findHabitsForCompletionWindow(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->prepareOrFail("
            SELECT id, frequency, target_days, start_date, end_date, created_at, archived_at
            FROM habits
            WHERE user_id = ?
              AND is_active = 1
              AND DATE(created_at) <= ?
              AND (archived_at IS NULL OR DATE(archived_at) > ?)
        ");
        $stmt->bind_param('iss', $userId, $endDate, $startDate);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function countCompletedHabitOccurrencesInRange(int $userId, string $startDate, string $endDate): int
    {
        $stmt = $this->prepareOrFail("
            SELECT COUNT(DISTINCT CONCAT(hc.habit_id, '|', hc.completion_date)) AS completed
            FROM habit_completions hc
            INNER JOIN habits h ON h.id = hc.habit_id AND h.user_id = hc.user_id
            WHERE hc.user_id = ?
              AND hc.completion_date BETWEEN ? AND ?
              AND h.is_active = 1
              AND hc.completion_date >= DATE(h.created_at)
              AND (h.archived_at IS NULL OR hc.completion_date < DATE(h.archived_at))
        ");
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['completed'] ?? 0);
    }

    public function countActiveDays(int $userId): int
    {
        $stmt = $this->prepareOrFail("
            SELECT COUNT(DISTINCT completion_date) AS total
            FROM habit_completions
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function findDistinctCompletionDatesDesc(int $userId): array
    {
        $stmt = $this->prepareOrFail("
            SELECT DISTINCT completion_date
            FROM habit_completions
            WHERE user_id = ?
            ORDER BY completion_date DESC
        ");
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        $rows = $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
        return array_map(static fn(array $row): string => (string) $row['completion_date'], $rows);
    }

    public function findBestStreak(int $userId): int
    {
        $stmt = $this->prepareOrFail("
            SELECT COALESCE(MAX(longest_streak), 0) AS best_streak
            FROM habits
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['best_streak'] ?? 0);
    }

    public function countTotalCompletions(int $userId): int
    {
        $stmt = $this->prepareOrFail("
            SELECT COUNT(*) AS total
            FROM habit_completions
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function findDailyCompletionCounts(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->prepareOrFail("
            SELECT DATE(completion_date) AS date, COUNT(*) AS completed
            FROM habit_completions
            WHERE user_id = ?
              AND completion_date BETWEEN ? AND ?
            GROUP BY DATE(completion_date)
            ORDER BY date ASC
        ");
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function findCategoryStats(int $userId): array
    {
        $stmt = $this->prepareOrFail("
            SELECT
                c.name AS category,
                COUNT(hc.id) AS total,
                ROUND((COUNT(hc.id) * 100.0 / (
                    SELECT COUNT(*)
                    FROM habit_completions
                    WHERE user_id = ?
                )), 1) AS percentage
            FROM habits h
            INNER JOIN habit_completions hc ON h.id = hc.habit_id
            LEFT JOIN categories c ON h.category_id = c.id
            WHERE h.user_id = ?
            GROUP BY c.id, c.name
            ORDER BY total DESC
        ");
        $stmt->bind_param('ii', $userId, $userId);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function findRecentHistory(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->prepareOrFail("
            WITH RECURSIVE date_range AS (
                SELECT ? AS day
                UNION ALL
                SELECT DATE_ADD(day, INTERVAL 1 DAY)
                FROM date_range
                WHERE day < ?
            ),
            initial_base AS (
                SELECT COUNT(*) AS initial_total
                FROM habits
                WHERE user_id = ?
                  AND DATE(created_at) < ?
            ),
            created_daily AS (
                SELECT DATE(created_at) AS day, COUNT(*) AS created_count
                FROM habits
                WHERE user_id = ?
                  AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
            ),
            completed_daily AS (
                SELECT completion_date AS day, COUNT(*) AS completed_count
                FROM habit_completions
                WHERE user_id = ?
                  AND completion_date BETWEEN ? AND ?
                GROUP BY completion_date
            )
            SELECT
                dr.day AS date,
                COALESCE(cd.completed_count, 0) AS completed,
                ib.initial_total + SUM(COALESCE(cr.created_count, 0)) OVER (ORDER BY dr.day) AS total,
                CASE
                    WHEN (ib.initial_total + SUM(COALESCE(cr.created_count, 0)) OVER (ORDER BY dr.day)) > 0 THEN
                        ROUND(
                            (COALESCE(cd.completed_count, 0) * 100.0)
                            / (ib.initial_total + SUM(COALESCE(cr.created_count, 0)) OVER (ORDER BY dr.day)),
                            1
                        )
                    ELSE 0
                END AS percentage
            FROM date_range dr
            CROSS JOIN initial_base ib
            LEFT JOIN created_daily cr ON cr.day = dr.day
            LEFT JOIN completed_daily cd ON cd.day = dr.day
            ORDER BY dr.day DESC
        ");
        $stmt->bind_param('ssisississ', $startDate, $endDate, $userId, $startDate, $userId, $startDate, $endDate, $userId, $startDate, $endDate);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
    }
}

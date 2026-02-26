<?php

namespace App\Repository;

final class AchievementRepository
{
    use InteractsWithDatabase;

    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findActiveWithUserUnlockData(int $userId): array
    {
        return $this->findActiveDefinitionsWithUserUnlockData($userId);
    }

    public function findActiveDefinitionsWithUserUnlockData(int $userId): array
    {
        $stmt = $this->prepareOrFail(
            "SELECT
                d.id,
                d.slug,
                d.name,
                d.description,
                d.icon,
                d.badge_color,
                d.rarity,
                d.points,
                d.rule_key,
                d.rule_config_json,
                d.sort_order,
                d.version,
                uau.id AS user_achievement_unlock_id,
                uau.unlocked_at,
                uau.awarded_points
            FROM achievement_definitions d
            LEFT JOIN user_achievement_unlocks uau
                ON uau.achievement_definition_id = d.id
               AND uau.user_id = ?
            WHERE d.is_active = 1
            ORDER BY d.sort_order ASC, d.id ASC"
        );

        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function insertUnlock(
        int $userId,
        int $achievementDefinitionId,
        int $awardedPoints,
        int $ruleVersion,
        string $source,
        string $unlockedAt
    ): bool {
        $stmt = $this->prepareOrFail(
            'INSERT IGNORE INTO user_achievement_unlocks (user_id, achievement_definition_id, unlocked_at, awarded_points, rule_version, source)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iisiis', $userId, $achievementDefinitionId, $unlockedAt, $awardedPoints, $ruleVersion, $source);
        $this->executeOrFail($stmt);

        return $stmt->affected_rows > 0;
    }

    public function insertUnlockEvent(
        int $userId,
        int $achievementDefinitionId,
        string $eventAt,
        string $payloadJson,
        string $source = 'live_sync'
    ): void {
        $stmt = $this->prepareOrFail(
            'INSERT INTO user_achievement_events (user_id, achievement_definition_id, event_type, event_at, payload_json, source)
             VALUES (?, ?, \'unlocked\', ?, ?, ?)'
        );
        $stmt->bind_param('iisss', $userId, $achievementDefinitionId, $eventAt, $payloadJson, $source);
        $this->executeOrFail($stmt);
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
        $result = $this->conn->query('SELECT COUNT(*) AS total FROM achievement_definitions WHERE is_active = 1');
        $row = $result ? $result->fetch_assoc() : [];

        return (int) ($row['total'] ?? 0);
    }

    public function countActiveDefinitions(): int
    {
        return $this->countActiveAchievements();
    }

    public function countUnlockedAchievements(int $userId): int
    {
        $stmt = $this->prepareOrFail('SELECT COUNT(*) AS total FROM user_achievement_unlocks WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];
        return (int) ($row['total'] ?? 0);
    }

    public function getProgressionLevels(): array
    {
        $result = $this->queryOrFail('SELECT level, xp_required_total, title, badge_color, is_active FROM progression_levels WHERE is_active = 1 ORDER BY level ASC');

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findProfileBadgeRewardDefinitions(): array
    {
        $result = $this->queryOrFail(
            "SELECT id, slug, reward_type, name, description, icon, visual_config_json, unlock_source_type, unlock_source_config_json, sort_order
             FROM reward_definitions
             WHERE is_active = 1 AND reward_type = 'profile_badge'
             ORDER BY sort_order ASC, id ASC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function insertUserRewardUnlock(
        int $userId,
        int $rewardDefinitionId,
        string $unlockedAt,
        string $source,
        ?string $sourceRef = null
    ): bool {
        $stmt = $this->prepareOrFail(
            'INSERT IGNORE INTO user_reward_unlocks (user_id, reward_definition_id, unlocked_at, source, source_ref)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iisss', $userId, $rewardDefinitionId, $unlockedAt, $source, $sourceRef);
        $this->executeOrFail($stmt);

        return $stmt->affected_rows > 0;
    }

    public function insertUserRewardEvent(
        int $userId,
        int $rewardDefinitionId,
        string $eventAt,
        string $payloadJson,
        string $source = 'progress_sync'
    ): void {
        $stmt = $this->prepareOrFail(
            'INSERT INTO user_reward_events (user_id, reward_definition_id, event_type, event_at, payload_json, source)
             VALUES (?, ?, \'unlocked\', ?, ?, ?)'
        );
        $stmt->bind_param('iisss', $userId, $rewardDefinitionId, $eventAt, $payloadJson, $source);
        $this->executeOrFail($stmt);
    }

    public function findUserProfileBadges(int $userId): array
    {
        $stmt = $this->prepareOrFail(
            "SELECT
                r.id,
                r.slug,
                r.name,
                r.description,
                r.icon,
                r.visual_config_json,
                uru.unlocked_at
             FROM user_reward_unlocks uru
             INNER JOIN reward_definitions r ON r.id = uru.reward_definition_id
             WHERE uru.user_id = ?
               AND r.reward_type = 'profile_badge'
               AND r.is_active = 1
             ORDER BY uru.unlocked_at DESC, r.sort_order ASC"
        );
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function countCompletionRecords(int $userId): int
    {
        $stmt = $this->prepareOrFail(
            "SELECT COUNT(DISTINCT CONCAT(hc.habit_id, '|', hc.completion_date)) AS total
             FROM habit_completions hc
             INNER JOIN habits h ON h.id = hc.habit_id AND h.user_id = hc.user_id
             WHERE hc.user_id = ?
               AND hc.completion_date >= DATE(h.created_at)
               AND (h.archived_at IS NULL OR hc.completion_date < DATE(h.archived_at))"
        );
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function getCompletionDatesAsc(int $userId): array
    {
        $stmt = $this->prepareOrFail(
            'SELECT DISTINCT completion_date FROM habit_completions WHERE user_id = ? ORDER BY completion_date ASC'
        );
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);

        $rows = $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
        return array_map(static fn(array $row): string => (string) $row['completion_date'], $rows);
    }

    public function countActiveDays(int $userId): int
    {
        $stmt = $this->prepareOrFail('SELECT COUNT(DISTINCT completion_date) AS total FROM habit_completions WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function countCreatedHabits(int $userId): int
    {
        $stmt = $this->prepareOrFail('SELECT COUNT(*) AS total FROM habits WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function findMaxCategoryCompletions(int $userId): int
    {
        $stmt = $this->prepareOrFail(
            "SELECT COALESCE(MAX(category_total), 0) AS max_total
             FROM (
                SELECT COUNT(hc.id) AS category_total
                FROM habit_completions hc
                INNER JOIN habits h ON h.id = hc.habit_id AND h.user_id = hc.user_id
                WHERE hc.user_id = ?
                GROUP BY h.category_id
             ) t"
        );
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['max_total'] ?? 0);
    }

    public function countWeekdayCoverage(int $userId): int
    {
        $stmt = $this->prepareOrFail(
            'SELECT COUNT(DISTINCT DAYOFWEEK(completion_date)) AS total FROM habit_completions WHERE user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function countTimeOfDayVariety(int $userId): int
    {
        $stmt = $this->prepareOrFail(
            "SELECT COUNT(DISTINCT h.time_of_day) AS total
             FROM habit_completions hc
             INNER JOIN habits h ON h.id = hc.habit_id AND h.user_id = hc.user_id
             WHERE hc.user_id = ?
               AND h.time_of_day IN ('morning', 'afternoon', 'evening')"
        );
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    public function findHabitsForScheduleWindow(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->prepareOrFail(
            "SELECT id, frequency, target_days, start_date, end_date, created_at, archived_at
             FROM habits
             WHERE user_id = ?
               AND DATE(created_at) <= ?
               AND (archived_at IS NULL OR DATE(archived_at) > ?)"
        );
        $stmt->bind_param('iss', $userId, $endDate, $startDate);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);
    }

    public function getDailyCompletionsMap(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->prepareOrFail(
            "SELECT completion_date, COUNT(DISTINCT habit_id) AS completed
             FROM habit_completions
             WHERE user_id = ?
               AND completion_date BETWEEN ? AND ?
             GROUP BY completion_date"
        );
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $this->executeOrFail($stmt);
        $rows = $this->getResultOrFail($stmt)->fetch_all(MYSQLI_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(string) ($row['completion_date'] ?? '')] = (int) ($row['completed'] ?? 0);
        }

        return $map;
    }
}

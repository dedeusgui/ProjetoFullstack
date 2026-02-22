<?php

namespace App\Achievements;

class AchievementService
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getUserAchievements(int $userId): array
    {
        return $this->syncUserAchievements($userId);
    }

    public function syncUserAchievements(int $userId): array
    {
        $totalHabits = $this->getTotalHabits($userId);
        $totalCompletions = $this->getTotalCompletions($userId);
        $bestStreak = $this->getBestStreak($userId);
        $perfectStreak = $this->getPerfectDaysStreak($userId, 730);

        $metrics = [
            'streak' => $bestStreak,
            'total_completions' => $totalCompletions,
            'habits_count' => $totalHabits,
            'perfect_week' => $perfectStreak,
            'perfect_month' => $perfectStreak,
        ];

        $categoryByCriteria = [
            'streak' => 'consistencia',
            'perfect_week' => 'consistencia',
            'perfect_month' => 'consistencia',
            'habits_count' => 'exploracao',
            'total_completions' => 'performance',
        ];

        $tierByRarity = [
            'common' => 'bronze',
            'rare' => 'prata',
            'epic' => 'ouro',
            'legendary' => 'ouro',
        ];

        $unlockedMap = [];
        $unlockedStmt = $this->conn->prepare("
            SELECT achievement_id, unlocked_at
            FROM user_achievements
            WHERE user_id = ?
        ");
        $unlockedStmt->bind_param('i', $userId);
        $unlockedStmt->execute();
        $unlockedResult = $unlockedStmt->get_result();
        while ($row = $unlockedResult->fetch_assoc()) {
            $unlockedMap[(int) $row['achievement_id']] = $row['unlocked_at'];
        }

        $achievementsStmt = $this->conn->prepare("
            SELECT id, slug, name, description, icon, badge_color, criteria_type, criteria_value, points, rarity
            FROM achievements
            WHERE is_active = 1
            ORDER BY criteria_value ASC, id ASC
        ");
        $achievementsStmt->execute();
        $achievementsResult = $achievementsStmt->get_result();

        $achievements = [];
        $justUnlockedIds = [];

        while ($achievement = $achievementsResult->fetch_assoc()) {
            $achievementId = (int) $achievement['id'];
            $criteriaType = (string) $achievement['criteria_type'];
            $criteriaValue = max(1, (int) $achievement['criteria_value']);
            $metricValue = (int) ($metrics[$criteriaType] ?? 0);

            if ($criteriaType === 'perfect_week') {
                $targetDays = 7 * $criteriaValue;
                $progress = min(100, (int) round(($metricValue / $targetDays) * 100));
                $isUnlocked = $metricValue >= $targetDays;
            } elseif ($criteriaType === 'perfect_month') {
                $targetDays = 30 * $criteriaValue;
                $progress = min(100, (int) round(($metricValue / $targetDays) * 100));
                $isUnlocked = $metricValue >= $targetDays;
            } else {
                $progress = min(100, (int) round(($metricValue / $criteriaValue) * 100));
                $isUnlocked = $metricValue >= $criteriaValue;
            }

            if ($isUnlocked && !isset($unlockedMap[$achievementId])) {
                $insertStmt = $this->conn->prepare("
                    INSERT INTO user_achievements (user_id, achievement_id, progress)
                    VALUES (?, ?, ?)
                ");
                $insertStmt->bind_param('iii', $userId, $achievementId, $progress);
                $insertStmt->execute();

                $unlockedMap[$achievementId] = date('Y-m-d H:i:s');
                $justUnlockedIds[$achievementId] = true;
            }

            $currentValue = 0;
            $targetValue = $criteriaValue;

            if ($criteriaType === 'perfect_week') {
                $targetValue = 7 * $criteriaValue;
                $currentValue = $metricValue;
                $progressLabel = $currentValue . '/' . $targetValue . ' dias perfeitos';
            } elseif ($criteriaType === 'perfect_month') {
                $targetValue = 30 * $criteriaValue;
                $currentValue = $metricValue;
                $progressLabel = $currentValue . '/' . $targetValue . ' dias perfeitos';
            } else {
                $currentValue = min($metricValue, $criteriaValue);
                $progressLabel = $currentValue . '/' . $criteriaValue;
            }

            $achievements[] = [
                'id' => $achievementId,
                'slug' => $achievement['slug'],
                'name' => $achievement['name'],
                'description' => $achievement['description'],
                'icon' => self::mapIconToBootstrap($achievement['icon'] ?? ''),
                'badge_color' => $achievement['badge_color'] ?? '#4a74ff',
                'criteria_type' => $criteriaType,
                'criteria_value' => $criteriaValue,
                'points' => (int) ($achievement['points'] ?? 0),
                'rarity' => $achievement['rarity'],
                'progress' => $progress,
                'progress_percent' => $progress,
                'progress_current' => $currentValue,
                'progress_target' => $targetValue,
                'progress_label' => $progressLabel,
                'is_near_completion' => !$isUnlocked && $progress >= 80,
                'category' => $categoryByCriteria[$criteriaType] ?? 'performance',
                'tier' => $tierByRarity[$achievement['rarity']] ?? 'bronze',
                'unlocked' => isset($unlockedMap[$achievementId]) || $isUnlocked,
                'just_unlocked' => isset($justUnlockedIds[$achievementId]),
                'date' => $unlockedMap[$achievementId] ?? null,
            ];
        }

        return $achievements;
    }

    public static function mapIconToBootstrap(string $icon): string
    {
        $normalized = strtolower(trim($icon));

        $map = [
            'flag' => 'bi bi-flag-fill',
            'fire' => 'bi bi-fire',
            'trophy' => 'bi bi-trophy-fill',
            'star' => 'bi bi-star-fill',
            'award' => 'bi bi-award-fill',
            'collection' => 'bi bi-collection-fill',
            'rocket' => 'bi bi-rocket-takeoff-fill',
            'gem' => 'bi bi-gem',
            'patch-check' => 'bi bi-patch-check-fill',
            'check' => 'bi bi-check-circle-fill',
        ];

        if ($normalized === '') {
            return 'bi bi-patch-check-fill';
        }

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        if (str_starts_with($normalized, 'bi bi-')) {
            return $normalized;
        }

        if (str_starts_with($normalized, 'bi-')) {
            return 'bi ' . $normalized;
        }

        return 'bi bi-patch-check-fill';
    }

    private function getTotalHabits(int $userId): int
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM habits
            WHERE user_id = ? AND is_active = 1 AND archived_at IS NULL
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    private function getTotalCompletions(int $userId): int
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM habit_completions
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    private function getBestStreak(int $userId): int
    {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(MAX(longest_streak), 0) AS best_streak
            FROM habits
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['best_streak'] ?? 0);
    }

    public function getDailyCompletionsMap(int $userId, int $days = 365): array
    {
        $days = max(1, $days);
        $today = $this->getUserTodayDate($userId);
        $startDate = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));

        $stmt = $this->conn->prepare("
            SELECT completion_date, COUNT(DISTINCT habit_id) AS completed
            FROM habit_completions
            WHERE user_id = ?
              AND completion_date BETWEEN ? AND ?
            GROUP BY completion_date
        ");
        $stmt->bind_param('iss', $userId, $startDate, $today);
        $stmt->execute();
        $result = $stmt->get_result();

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $map[$row['completion_date']] = (int) $row['completed'];
        }

        return $map;
    }

    public function getPerfectDaysStreak(int $userId, int $days = 365): int
    {
        $days = max(1, $days);
        $totalHabits = $this->getTotalHabits($userId);
        if ($totalHabits <= 0) {
            return 0;
        }

        $dailyMap = $this->getDailyCompletionsMap($userId, $days);
        $today = $this->getUserTodayDate($userId);

        $maxStreak = 0;
        $currentStreak = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime($today . " -$i days"));
            $completed = $dailyMap[$date] ?? 0;

            if ($completed >= $totalHabits) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
                continue;
            }

            $currentStreak = 0;
        }

        return $maxStreak;
    }

    private function getUserTodayDate(int $userId): string
    {
        static $cache = [];
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $timezone = 'America/Sao_Paulo';
        $stmt = $this->conn->prepare("SELECT timezone FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!empty($row['timezone'])) {
                $timezone = (string) $row['timezone'];
            }
        }

        try {
            $now = new \DateTime('now', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            $now = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        }

        $cache[$userId] = $now->format('Y-m-d');
        return $cache[$userId];
    }
}

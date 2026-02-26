<?php

namespace App\Achievements;

use App\Repository\AchievementRepository;
use App\Support\UserLocalDateResolver;

class AchievementService
{
    private \mysqli $conn;
    private UserLocalDateResolver $userLocalDateResolver;
    private AchievementRepository $achievementRepository;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->userLocalDateResolver = new UserLocalDateResolver($conn);
        $this->achievementRepository = new AchievementRepository($conn);
    }

    public function getUserAchievements(int $userId): array
    {
        return $this->buildUserAchievements($userId, false);
    }

    public function getAchievementsPageData(int $userId): array
    {
        $achievements = $this->syncUserAchievements($userId);
        $userRow = $this->achievementRepository->getUserLevelAndXp($userId);
        $totalAvailable = max(1, $this->achievementRepository->countActiveAchievements());
        $unlockedCount = (int) count(array_filter($achievements, static fn(array $item): bool => (bool) ($item['unlocked'] ?? false)));
        $progressPercent = min(100, round(($unlockedCount / $totalAvailable) * 100, 1));

        $currentLevel = max(1, (int) ($userRow['level'] ?? 1));
        $totalXp = max(0, (int) ($userRow['experience_points'] ?? 0));
        $xpLevelStart = $this->xpRequiredToReachLevel($currentLevel);
        $xpLevelEnd = $this->xpRequiredToReachLevel($currentLevel + 1);
        $xpIntoCurrent = max(0, $totalXp - $xpLevelStart);
        $xpNeeded = max(1, $xpLevelEnd - $xpLevelStart);

        $recentUnlocked = array_values(array_filter($achievements, static fn(array $item): bool => !empty($item['unlocked']) && !empty($item['date'])));
        usort($recentUnlocked, static fn(array $a, array $b): int => strtotime((string) ($b['date'] ?? '1970-01-01')) <=> strtotime((string) ($a['date'] ?? '1970-01-01')));
        $recentUnlockedTimeline = array_slice($recentUnlocked, 0, 5);

        $rarityOrder = ['legendary' => 4, 'epic' => 3, 'rare' => 2, 'common' => 1];
        $rarestUnlocked = $recentUnlocked;
        usort($rarestUnlocked, static fn(array $a, array $b): int => ($rarityOrder[$b['rarity'] ?? 'common'] ?? 0) <=> ($rarityOrder[$a['rarity'] ?? 'common'] ?? 0));

        $nextAchievement = array_values(array_filter($achievements, static fn(array $item): bool => empty($item['unlocked'])));
        usort($nextAchievement, static fn(array $a, array $b): int => ($b['progress_percent'] ?? 0) <=> ($a['progress_percent'] ?? 0));

        return [
            'achievements' => $achievements,
            'hero' => [
                'name' => (string) ($userRow['name'] ?? ''),
                'level' => $currentLevel,
                'total_xp' => $totalXp,
                'unlocked_count' => $unlockedCount,
                'total_available' => $totalAvailable,
                'progress_percent' => $progressPercent,
                'xp_progress_percent' => min(100, round(($xpIntoCurrent / $xpNeeded) * 100, 1)),
                'xp_to_next_level' => max(0, $xpLevelEnd - $totalXp),
                'xp_needed_for_level' => $xpNeeded,
                'rank_label' => $this->resolveRankLabel($unlockedCount, $totalAvailable),
            ],
            'highlights' => [
                'latest_unlocked' => $recentUnlocked[0] ?? null,
                'rarest_unlocked' => $rarestUnlocked[0] ?? null,
                'next_achievement' => $nextAchievement[0] ?? null,
            ],
            'recent_unlocked' => $recentUnlockedTimeline,
            'stats' => [
                'legendary_unlocked' => count(array_filter($achievements, static fn(array $item): bool => !empty($item['unlocked']) && ($item['rarity'] ?? '') === 'legendary')),
                'overall_progress_percent' => $progressPercent,
            ],
        ];
    }

    public function syncUserAchievements(int $userId): array
    {
        return $this->buildUserAchievements($userId, true);
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

    private function resolveProgressPercentAndTarget(string $criteriaType, int $criteriaValue, int $metricValue): array
    {
        if ($criteriaType === 'perfect_week') {
            $target = 7 * $criteriaValue;
            return [min(100, (int) round(($metricValue / max(1, $target)) * 100)), $target];
        }

        if ($criteriaType === 'perfect_month') {
            $target = 30 * $criteriaValue;
            return [min(100, (int) round(($metricValue / max(1, $target)) * 100)), $target];
        }

        return [min(100, (int) round(($metricValue / max(1, $criteriaValue)) * 100)), $criteriaValue];
    }

    private function resolveRankLabel(int $unlockedCount, int $totalAvailable): string
    {
        $ratio = $totalAvailable > 0 ? ($unlockedCount / $totalAvailable) : 0;

        if ($ratio >= 0.85) {
            return 'Lenda';
        }

        if ($ratio >= 0.6) {
            return 'Mestre';
        }

        if ($ratio >= 0.3) {
            return 'Intermediário';
        }

        return 'Iniciante';
    }

    private function xpRequiredToReachLevel(int $level): int
    {
        $level = max(1, $level);
        return (int) (150 * (($level - 1) * $level));
    }

    private function getTotalHabits(int $userId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM habits WHERE user_id = ? AND is_active = 1 AND archived_at IS NULL");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    private function getTotalCompletions(int $userId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM habit_completions WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    private function getBestStreak(int $userId): int
    {
        $stmt = $this->conn->prepare("SELECT COALESCE(MAX(longest_streak), 0) AS best_streak FROM habits WHERE user_id = ? AND is_active = 1");
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

        $stmt = $this->conn->prepare("SELECT completion_date, COUNT(DISTINCT habit_id) AS completed FROM habit_completions WHERE user_id = ? AND completion_date BETWEEN ? AND ? GROUP BY completion_date");
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
        return $this->userLocalDateResolver->getTodayDateForUser($userId);
    }

    private function buildUserAchievements(int $userId, bool $persistUnlocked): array
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

        $rows = $this->achievementRepository->findActiveWithUserUnlockData($userId);

        $achievements = [];
        $justUnlockedIds = [];

        foreach ($rows as $achievement) {
            $achievementId = (int) $achievement['id'];
            $criteriaType = (string) $achievement['criteria_type'];
            $criteriaValue = max(1, (int) $achievement['criteria_value']);
            $metricValue = (int) ($metrics[$criteriaType] ?? 0);

            [$progressPercent, $targetValue] = $this->resolveProgressPercentAndTarget($criteriaType, $criteriaValue, $metricValue);
            $currentValue = min($metricValue, $targetValue);
            $isUnlocked = $currentValue >= $targetValue;

            if ($persistUnlocked && $isUnlocked && empty($achievement['user_achievement_id'])) {
                $insertStmt = $this->conn->prepare("INSERT INTO user_achievements (user_id, achievement_id, progress) VALUES (?, ?, ?)");
                $insertStmt->bind_param('iii', $userId, $achievementId, $currentValue);
                $insertStmt->execute();

                $achievement['unlocked_at'] = date('Y-m-d H:i:s');
                $justUnlockedIds[$achievementId] = true;
            }

            $progressLabel = $criteriaType === 'perfect_week' || $criteriaType === 'perfect_month'
                ? $currentValue . '/' . $targetValue . ' dias perfeitos'
                : $currentValue . '/' . $targetValue;

            $unlockedAt = $achievement['unlocked_at'] ?? null;

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
                'progress' => $currentValue,
                'progress_percent' => $progressPercent,
                'progress_current' => $currentValue,
                'progress_target' => $targetValue,
                'progress_label' => $progressLabel,
                'is_near_completion' => !$isUnlocked && $progressPercent >= 80,
                'category' => $categoryByCriteria[$criteriaType] ?? 'performance',
                'tier' => $tierByRarity[$achievement['rarity']] ?? 'bronze',
                'unlocked' => !empty($unlockedAt) || $isUnlocked,
                'just_unlocked' => $persistUnlocked && isset($justUnlockedIds[$achievementId]),
                'date' => $unlockedAt,
            ];
        }

        usort($achievements, static function (array $a, array $b): int {
            if (($a['unlocked'] ?? false) !== ($b['unlocked'] ?? false)) {
                return ($a['unlocked'] ?? false) ? -1 : 1;
            }

            $rarityOrder = ['legendary' => 4, 'epic' => 3, 'rare' => 2, 'common' => 1];
            $aRarity = $rarityOrder[$a['rarity'] ?? 'common'] ?? 0;
            $bRarity = $rarityOrder[$b['rarity'] ?? 'common'] ?? 0;
            if ($aRarity !== $bRarity) {
                return $bRarity <=> $aRarity;
            }

            return ($b['progress_percent'] ?? 0) <=> ($a['progress_percent'] ?? 0);
        });

        return $achievements;
    }
}

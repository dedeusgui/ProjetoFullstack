<?php

namespace App\Achievements;

use App\Habits\HabitSchedulePolicy;
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
        [$xpIntoCurrent, $xpNeeded, $xpToNext] = $this->buildHeroXpState($totalXp, $currentLevel);

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
                'xp_progress_percent' => min(100, round(($xpIntoCurrent / max(1, $xpNeeded)) * 100, 1)),
                'xp_to_next_level' => max(0, $xpToNext),
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
            'calendar' => 'bi bi-calendar-check-fill',
            'clock' => 'bi bi-clock-fill',
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

    public function getDailyCompletionsMap(int $userId, int $days = 365): array
    {
        $days = max(1, $days);
        $today = $this->getUserTodayDate($userId);
        $startDate = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));

        return $this->achievementRepository->getDailyCompletionsMap($userId, $startDate, $today);
    }

    public function getPerfectDaysStreak(int $userId, int $days = 365): int
    {
        $days = max(1, $days);
        $today = $this->getUserTodayDate($userId);
        $startDate = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));

        $dailyMap = $this->achievementRepository->getDailyCompletionsMap($userId, $startDate, $today);
        $scheduledMap = $this->buildScheduledCountsByDate($userId, $startDate, $today);

        $maxStreak = 0;
        $currentStreak = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime($today . " -$i days"));
            $scheduled = (int) ($scheduledMap[$date] ?? 0);
            $completed = (int) ($dailyMap[$date] ?? 0);

            if ($scheduled > 0 && $completed >= $scheduled) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
                continue;
            }

            $currentStreak = 0;
        }

        return $maxStreak;
    }

    private function buildUserAchievements(int $userId, bool $persistUnlocked): array
    {
        $totalCompletions = $this->getTotalCompletions($userId);
        $bestStreak = $this->getBestStreak($userId);
        $perfectStreak = $this->getPerfectDaysStreak($userId, 730);
        $createdHabitsCount = $this->achievementRepository->countCreatedHabits($userId);
        $activeDays = $this->achievementRepository->countActiveDays($userId);
        $maxCategoryCompletions = $this->achievementRepository->findMaxCategoryCompletions($userId);
        $weekdayCoverage = $this->achievementRepository->countWeekdayCoverage($userId);
        $timeOfDayVariety = $this->achievementRepository->countTimeOfDayVariety($userId);
        $completionDates = $this->achievementRepository->getCompletionDatesAsc($userId);

        $baseMetrics = [
            'streak_days' => $bestStreak,
            'total_completions' => $totalCompletions,
            'habits_created' => $createdHabitsCount,
            'perfect_days_streak' => $perfectStreak,
            'active_days' => $activeDays,
            'max_category_completions' => $maxCategoryCompletions,
            'weekday_coverage' => $weekdayCoverage,
            'time_of_day_variety' => $timeOfDayVariety,
        ];

        $categoryByRule = [
            'streak_days' => 'consistencia',
            'perfect_days_streak' => 'consistencia',
            'active_days' => 'consistencia',
            'habits_created' => 'exploracao',
            'weekday_coverage' => 'exploracao',
            'time_of_day_variety' => 'exploracao',
            'total_completions' => 'performance',
            'max_category_completions' => 'performance',
            'comeback_count' => 'performance',
        ];

        $tierByRarity = [
            'common' => 'bronze',
            'rare' => 'prata',
            'epic' => 'ouro',
            'legendary' => 'ouro',
        ];

        $rows = $this->achievementRepository->findActiveDefinitionsWithUserUnlockData($userId);
        $achievements = [];
        $justUnlockedIds = [];

        foreach ($rows as $row) {
            $achievementId = (int) ($row['id'] ?? 0);
            $ruleKey = (string) ($row['rule_key'] ?? '');
            $ruleConfig = $this->decodeRuleConfig($row['rule_config_json'] ?? null);
            $evaluation = $this->evaluateRule($ruleKey, $ruleConfig, $baseMetrics, $completionDates);

            $targetValue = max(1, (int) ($evaluation['target'] ?? 1));
            $metricValue = max(0, (int) ($evaluation['current'] ?? 0));
            $currentValue = min($metricValue, $targetValue);
            $progressPercent = min(100, (int) round(($metricValue / $targetValue) * 100));
            $isUnlocked = $metricValue >= $targetValue;

            $now = date('Y-m-d H:i:s');
            if (
                $persistUnlocked
                && $isUnlocked
                && empty($row['user_achievement_unlock_id'])
            ) {
                $inserted = $this->achievementRepository->insertUnlock(
                    $userId,
                    $achievementId,
                    (int) ($row['points'] ?? 0),
                    max(1, (int) ($row['version'] ?? 1)),
                    'live_sync',
                    $now
                );

                if ($inserted) {
                    $payloadJson = json_encode([
                        'current' => $metricValue,
                        'target' => $targetValue,
                        'rule_key' => $ruleKey,
                        'rule_config' => $ruleConfig,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $this->achievementRepository->insertUnlockEvent(
                        $userId,
                        $achievementId,
                        $now,
                        $payloadJson !== false ? $payloadJson : '{}',
                        'live_sync'
                    );
                    $row['unlocked_at'] = $now;
                    $justUnlockedIds[$achievementId] = true;
                }
            }

            $criteriaInfo = $this->mapRuleToLegacyCriteria($ruleKey, $ruleConfig);
            $progressLabel = $this->buildProgressLabel($ruleKey, $currentValue, $targetValue);
            $unlockedAt = $row['unlocked_at'] ?? null;

            $achievements[] = [
                'id' => $achievementId,
                'slug' => (string) ($row['slug'] ?? ''),
                'name' => (string) ($row['name'] ?? 'Conquista'),
                'description' => (string) ($row['description'] ?? ''),
                'icon' => self::mapIconToBootstrap((string) ($row['icon'] ?? '')),
                'badge_color' => (string) ($row['badge_color'] ?? '#4a74ff'),
                'criteria_type' => $criteriaInfo['criteria_type'],
                'criteria_value' => $criteriaInfo['criteria_value'],
                'points' => (int) ($row['points'] ?? 0),
                'rarity' => (string) ($row['rarity'] ?? 'common'),
                'progress' => $currentValue,
                'progress_percent' => $progressPercent,
                'progress_current' => $currentValue,
                'progress_target' => $targetValue,
                'progress_label' => $progressLabel,
                'is_near_completion' => !$isUnlocked && $progressPercent >= 80,
                'category' => $categoryByRule[$ruleKey] ?? 'performance',
                'tier' => $tierByRarity[(string) ($row['rarity'] ?? 'common')] ?? 'bronze',
                'unlocked' => !empty($unlockedAt) || $isUnlocked,
                'just_unlocked' => $persistUnlocked && isset($justUnlockedIds[$achievementId]),
                'date' => $unlockedAt,
                'rule_key' => $ruleKey,
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

            if (($a['progress_percent'] ?? 0) !== ($b['progress_percent'] ?? 0)) {
                return ($b['progress_percent'] ?? 0) <=> ($a['progress_percent'] ?? 0);
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $achievements;
    }

    private function decodeRuleConfig(mixed $raw): array
    {
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function evaluateRule(string $ruleKey, array $ruleConfig, array $baseMetrics, array $completionDates): array
    {
        $target = max(1, (int) ($ruleConfig['threshold'] ?? 1));
        $current = (int) ($baseMetrics[$ruleKey] ?? 0);

        if ($ruleKey === 'comeback_count') {
            $minGapDays = max(1, (int) ($ruleConfig['min_gap_days'] ?? 3));
            $current = $this->countComebacks($completionDates, $minGapDays);
        }

        return [
            'current' => $current,
            'target' => $target,
        ];
    }

    private function countComebacks(array $completionDates, int $minGapDays): int
    {
        if (count($completionDates) < 2) {
            return 0;
        }

        $count = 0;
        $previous = null;
        foreach ($completionDates as $date) {
            if (!is_string($date)) {
                continue;
            }

            if ($previous !== null) {
                $diffDays = (int) floor((strtotime($date) - strtotime($previous)) / 86400);
                $missedDays = max(0, $diffDays - 1);
                if ($missedDays >= $minGapDays) {
                    $count++;
                }
            }

            $previous = $date;
        }

        return $count;
    }

    private function buildProgressLabel(string $ruleKey, int $currentValue, int $targetValue): string
    {
        if ($ruleKey === 'perfect_days_streak') {
            return $currentValue . '/' . $targetValue . ' dias perfeitos';
        }

        if ($ruleKey === 'active_days') {
            return $currentValue . '/' . $targetValue . ' dias ativos';
        }

        return $currentValue . '/' . $targetValue;
    }

    private function mapRuleToLegacyCriteria(string $ruleKey, array $ruleConfig): array
    {
        $threshold = max(1, (int) ($ruleConfig['threshold'] ?? 1));

        return match ($ruleKey) {
            'streak_days' => ['criteria_type' => 'streak', 'criteria_value' => $threshold],
            'total_completions' => ['criteria_type' => 'total_completions', 'criteria_value' => $threshold],
            'habits_created' => ['criteria_type' => 'habits_count', 'criteria_value' => $threshold],
            'perfect_days_streak' => $threshold === 30
                ? ['criteria_type' => 'perfect_month', 'criteria_value' => 1]
                : ['criteria_type' => 'perfect_week', 'criteria_value' => max(1, (int) ceil($threshold / 7))],
            default => ['criteria_type' => $ruleKey, 'criteria_value' => $threshold],
        };
    }

    private function buildScheduledCountsByDate(int $userId, string $startDate, string $endDate): array
    {
        $habits = $this->achievementRepository->findHabitsForScheduleWindow($userId, $startDate, $endDate);
        $scheduledMap = [];

        if ($habits === []) {
            return $scheduledMap;
        }

        $current = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $scheduledMap[$date] = 0;

            foreach ($habits as $habit) {
                if ($this->isHabitEligibleForDate($habit, $date) && HabitSchedulePolicy::isScheduledForDate($habit, $date)) {
                    $scheduledMap[$date]++;
                }
            }

            $current->modify('+1 day');
        }

        return $scheduledMap;
    }

    private function isHabitEligibleForDate(array $habit, string $date): bool
    {
        $createdDate = !empty($habit['created_at']) ? date('Y-m-d', strtotime((string) $habit['created_at'])) : null;
        if ($createdDate !== null && $date < $createdDate) {
            return false;
        }

        if (!empty($habit['archived_at'])) {
            $archivedCutoff = date('Y-m-d', strtotime((string) $habit['archived_at']));
            if ($date >= $archivedCutoff) {
                return false;
            }
        }

        return true;
    }

    private function getTotalCompletions(int $userId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM habit_completions WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['total'] ?? 0);
    }

    private function getBestStreak(int $userId): int
    {
        $stmt = $this->conn->prepare('SELECT COALESCE(MAX(longest_streak), 0) AS best_streak FROM habits WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];

        return (int) ($row['best_streak'] ?? 0);
    }

    private function getUserTodayDate(int $userId): string
    {
        return $this->userLocalDateResolver->getTodayDateForUser($userId);
    }

    private function buildHeroXpState(int $totalXp, int $currentLevel): array
    {
        $levels = $this->achievementRepository->getProgressionLevels();
        if ($levels === []) {
            $xpLevelStart = (($currentLevel - 1) ** 2) * 120;
            $xpLevelEnd = ($currentLevel ** 2) * 120;

            return [
                max(0, $totalXp - $xpLevelStart),
                max(1, $xpLevelEnd - $xpLevelStart),
                max(0, $xpLevelEnd - $totalXp),
            ];
        }

        $currentThreshold = 0;
        $nextThreshold = null;
        foreach ($levels as $row) {
            $level = (int) ($row['level'] ?? 1);
            $xpRequired = (int) ($row['xp_required_total'] ?? 0);
            if ($level === $currentLevel) {
                $currentThreshold = $xpRequired;
            }
            if ($level === $currentLevel + 1) {
                $nextThreshold = $xpRequired;
                break;
            }
        }

        if ($nextThreshold === null) {
            $nextThreshold = $currentThreshold + 250;
        }

        $xpInto = max(0, $totalXp - $currentThreshold);
        $xpNeeded = max(1, $nextThreshold - $currentThreshold);
        $xpToNext = max(0, $nextThreshold - $totalXp);

        return [$xpInto, $xpNeeded, $xpToNext];
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
}

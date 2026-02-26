<?php

namespace App\UserProgress;

use App\Achievements\AchievementService;
use App\Repository\AchievementRepository;

class UserProgressService
{
    private const BASE_COMPLETION_XP = 10;

    private \mysqli $conn;
    private AchievementRepository $achievementRepository;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
        $this->achievementRepository = new AchievementRepository($conn);
    }

    public function getUserProgressSummary(int $userId, ?array $achievements = null): array
    {
        return $this->refreshUserProgressSummary($userId, $achievements);
    }

    public function refreshUserProgressSummary(int $userId, ?array $achievements = null): array
    {
        $achievementList = $achievements ?? (new AchievementService($this->conn))->syncUserAchievements($userId);
        $summary = $this->buildProgressSummary($userId, $achievementList);

        $this->persistUserProgress($userId, (int) $summary['level'], (int) $summary['total_xp']);
        $rewardSync = $this->syncLevelRewards($userId, (int) $summary['level']);

        $profileBadges = $this->mapProfileBadges($this->achievementRepository->findUserProfileBadges($userId));
        $summary['profile_badges'] = $profileBadges;
        $summary['total_badges_unlocked'] = count($profileBadges);
        $summary['reward_unlocks_new_count'] = $rewardSync['new_unlocks'] ?? 0;
        $summary['next_level_reward'] = $this->findNextLevelReward((int) $summary['level']);

        return $summary;
    }

    public function calculateLevelFromXp(int $totalXp): int
    {
        $totalXp = max(0, $totalXp);
        $level = 1;

        while (true) {
            $nextLevel = $level + 1;
            $nextThreshold = $this->xpRequiredForLevel($nextLevel);
            if ($totalXp < $nextThreshold) {
                return $level;
            }
            $level++;

            if ($level > 1000) {
                return $level;
            }
        }
    }

    public function persistUserProgress(int $userId, int $level, int $experiencePoints): void
    {
        static $hasLevelColumn = null;
        static $hasXpColumn = null;

        if ($hasLevelColumn === null) {
            $levelCheck = $this->conn->query("SHOW COLUMNS FROM users LIKE 'level'");
            $xpCheck = $this->conn->query("SHOW COLUMNS FROM users LIKE 'experience_points'");
            $hasLevelColumn = $levelCheck && $levelCheck->num_rows > 0;
            $hasXpColumn = $xpCheck && $xpCheck->num_rows > 0;
        }

        if (!$hasLevelColumn && !$hasXpColumn) {
            return;
        }

        if ($hasLevelColumn && $hasXpColumn) {
            $stmt = $this->conn->prepare('UPDATE users SET level = ?, experience_points = ? WHERE id = ?');
            $stmt->bind_param('iii', $level, $experiencePoints, $userId);
            $stmt->execute();
            return;
        }

        if ($hasLevelColumn) {
            $stmt = $this->conn->prepare('UPDATE users SET level = ? WHERE id = ?');
            $stmt->bind_param('ii', $level, $userId);
            $stmt->execute();
            return;
        }

        $stmt = $this->conn->prepare('UPDATE users SET experience_points = ? WHERE id = ?');
        $stmt->bind_param('ii', $experiencePoints, $userId);
        $stmt->execute();
    }

    private function buildProgressSummary(int $userId, array $achievementList): array
    {
        $unlockedAchievements = array_values(array_filter($achievementList, static function (array $achievement): bool {
            return !empty($achievement['unlocked']);
        }));

        $achievementXp = (int) array_sum(array_map(static function (array $achievement): int {
            return (int) ($achievement['points'] ?? 0);
        }, $unlockedAchievements));

        $completionCount = $this->achievementRepository->countCompletionRecords($userId);
        $completionXp = $completionCount * self::BASE_COMPLETION_XP;
        $totalXp = $achievementXp + $completionXp;

        $levelState = $this->resolveLevelState($totalXp);

        return [
            'level' => $levelState['level'],
            'level_title' => $levelState['title'],
            'total_xp' => $totalXp,
            'completion_xp' => $completionXp,
            'achievement_xp' => $achievementXp,
            'completion_count_for_xp' => $completionCount,
            'xp_into_level' => $levelState['xp_into_level'],
            'xp_needed_for_level' => $levelState['xp_needed_for_level'],
            'xp_to_next_level' => $levelState['xp_to_next_level'],
            'xp_progress_percent' => $levelState['xp_progress_percent'],
            'unlocked_achievements' => $unlockedAchievements,
            'unlocked_achievements_count' => count($unlockedAchievements),
            'achievements_count' => count($achievementList),
        ];
    }

    private function resolveLevelState(int $totalXp): array
    {
        $levels = $this->achievementRepository->getProgressionLevels();
        if ($levels === []) {
            $level = $this->calculateLevelFromXp($totalXp);
            $currentThreshold = $this->xpRequiredForLevel($level);
            $nextThreshold = $this->xpRequiredForLevel($level + 1);

            return $this->formatLevelState($level, 'Nível ' . $level, $totalXp, $currentThreshold, $nextThreshold);
        }

        $current = $levels[0];
        $next = null;
        foreach ($levels as $index => $row) {
            $xpRequired = (int) ($row['xp_required_total'] ?? 0);
            if ($xpRequired <= $totalXp) {
                $current = $row;
                $next = $levels[$index + 1] ?? null;
                continue;
            }

            $next = $row;
            break;
        }

        $level = max(1, (int) ($current['level'] ?? 1));
        $title = (string) ($current['title'] ?? ('Nível ' . $level));
        $currentThreshold = (int) ($current['xp_required_total'] ?? 0);
        $nextThreshold = $next !== null
            ? (int) ($next['xp_required_total'] ?? ($currentThreshold + 250))
            : ($currentThreshold + 250);

        return $this->formatLevelState($level, $title, $totalXp, $currentThreshold, $nextThreshold);
    }

    private function formatLevelState(int $level, string $title, int $totalXp, int $currentThreshold, int $nextThreshold): array
    {
        $xpIntoLevel = max(0, $totalXp - $currentThreshold);
        $xpNeededForLevel = max(1, $nextThreshold - $currentThreshold);
        $xpToNextLevel = max(0, $nextThreshold - $totalXp);

        return [
            'level' => $level,
            'title' => $title,
            'xp_into_level' => $xpIntoLevel,
            'xp_needed_for_level' => $xpNeededForLevel,
            'xp_to_next_level' => $xpToNextLevel,
            'xp_progress_percent' => min(100, (int) round(($xpIntoLevel / $xpNeededForLevel) * 100)),
        ];
    }

    private function xpRequiredForLevel(int $level): int
    {
        $level = max(1, $level);
        if ($level === 1) {
            return 0;
        }

        $xp = 0;
        for ($i = 2; $i <= $level; $i++) {
            if ($i <= 10) {
                $xp += 100;
            } elseif ($i <= 20) {
                $xp += 150;
            } elseif ($i <= 40) {
                $xp += 200;
            } else {
                $xp += 250;
            }
        }

        return $xp;
    }

    private function syncLevelRewards(int $userId, int $level): array
    {
        $definitions = $this->achievementRepository->findProfileBadgeRewardDefinitions();
        if ($definitions === []) {
            return ['new_unlocks' => 0];
        }

        $newUnlocks = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($definitions as $definition) {
            $config = [];
            if (!empty($definition['unlock_source_config_json']) && is_string($definition['unlock_source_config_json'])) {
                $decoded = json_decode((string) $definition['unlock_source_config_json'], true);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            }

            $requiredLevel = max(1, (int) ($config['level'] ?? 1));
            if ($level < $requiredLevel) {
                continue;
            }

            $rewardDefinitionId = (int) ($definition['id'] ?? 0);
            if ($rewardDefinitionId <= 0) {
                continue;
            }

            $inserted = $this->achievementRepository->insertUserRewardUnlock(
                $userId,
                $rewardDefinitionId,
                $now,
                'level_milestone',
                'level:' . $requiredLevel
            );

            if (!$inserted) {
                continue;
            }

            $payloadJson = json_encode([
                'level' => $level,
                'required_level' => $requiredLevel,
                'reward_slug' => $definition['slug'] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->achievementRepository->insertUserRewardEvent(
                $userId,
                $rewardDefinitionId,
                $now,
                $payloadJson !== false ? $payloadJson : '{}',
                'level_milestone'
            );
            $newUnlocks++;
        }

        return ['new_unlocks' => $newUnlocks];
    }

    private function mapProfileBadges(array $rows): array
    {
        return array_map(static function (array $row): array {
            $visual = [];
            if (!empty($row['visual_config_json']) && is_string($row['visual_config_json'])) {
                $decoded = json_decode((string) $row['visual_config_json'], true);
                if (is_array($decoded)) {
                    $visual = $decoded;
                }
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'slug' => (string) ($row['slug'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'icon' => AchievementService::mapIconToBootstrap((string) ($row['icon'] ?? 'patch-check')),
                'unlocked_at' => $row['unlocked_at'] ?? null,
                'color' => (string) ($visual['color'] ?? '#4a74ff'),
                'label' => (string) ($visual['label'] ?? ''),
            ];
        }, $rows);
    }

    private function findNextLevelReward(int $currentLevel): ?array
    {
        $definitions = $this->achievementRepository->findProfileBadgeRewardDefinitions();
        foreach ($definitions as $definition) {
            $config = [];
            if (!empty($definition['unlock_source_config_json']) && is_string($definition['unlock_source_config_json'])) {
                $decoded = json_decode((string) $definition['unlock_source_config_json'], true);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            }

            $requiredLevel = max(1, (int) ($config['level'] ?? 1));
            if ($requiredLevel <= $currentLevel) {
                continue;
            }

            return [
                'slug' => (string) ($definition['slug'] ?? ''),
                'name' => (string) ($definition['name'] ?? ''),
                'required_level' => $requiredLevel,
                'icon' => AchievementService::mapIconToBootstrap((string) ($definition['icon'] ?? 'patch-check')),
            ];
        }

        return null;
    }
}

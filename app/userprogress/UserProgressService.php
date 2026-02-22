<?php

namespace App\UserProgress;

use App\Achievements\AchievementService;

class UserProgressService
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getUserProgressSummary(int $userId, ?array $achievements = null): array
    {
        return $this->refreshUserProgressSummary($userId, $achievements);
    }

    public function refreshUserProgressSummary(int $userId, ?array $achievements = null): array
    {
        $achievementList = $achievements ?? (new AchievementService($this->conn))->syncUserAchievements($userId);
        $summary = $this->buildProgressSummary($achievementList);

        $this->persistUserProgress($userId, (int) $summary['level'], (int) $summary['total_xp']);

        return $summary;
    }

    public function calculateLevelFromXp(int $totalXp): int
    {
        return max(1, (int) floor(sqrt(max(0, $totalXp) / 120)) + 1);
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
            $stmt = $this->conn->prepare("
                UPDATE users
                SET level = ?, experience_points = ?
                WHERE id = ?
            ");
            $stmt->bind_param('iii', $level, $experiencePoints, $userId);
            $stmt->execute();
            return;
        }

        if ($hasLevelColumn) {
            $stmt = $this->conn->prepare("UPDATE users SET level = ? WHERE id = ?");
            $stmt->bind_param('ii', $level, $userId);
            $stmt->execute();
            return;
        }

        $stmt = $this->conn->prepare("UPDATE users SET experience_points = ? WHERE id = ?");
        $stmt->bind_param('ii', $experiencePoints, $userId);
        $stmt->execute();
    }

    private function buildProgressSummary(array $achievementList): array
    {
        $unlockedAchievements = array_values(array_filter($achievementList, static function (array $achievement): bool {
            return !empty($achievement['unlocked']);
        }));

        $totalXp = (int) array_sum(array_map(static function (array $achievement): int {
            return (int) ($achievement['points'] ?? 0);
        }, $unlockedAchievements));

        $currentLevel = $this->calculateLevelFromXp($totalXp);
        $xpLevelStart = (($currentLevel - 1) ** 2) * 120;
        $xpLevelEnd = ($currentLevel ** 2) * 120;
        $xpIntoCurrentLevel = max(0, $totalXp - $xpLevelStart);
        $xpNeededForLevel = max(1, $xpLevelEnd - $xpLevelStart);

        return [
            'level' => $currentLevel,
            'total_xp' => $totalXp,
            'xp_into_level' => $xpIntoCurrentLevel,
            'xp_needed_for_level' => $xpNeededForLevel,
            'xp_to_next_level' => max(0, $xpLevelEnd - $totalXp),
            'xp_progress_percent' => min(100, (int) round(($xpIntoCurrentLevel / $xpNeededForLevel) * 100)),
            'unlocked_achievements' => $unlockedAchievements,
            'unlocked_achievements_count' => count($unlockedAchievements),
            'achievements_count' => count($achievementList),
        ];
    }
}

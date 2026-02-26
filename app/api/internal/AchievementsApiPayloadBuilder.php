<?php

namespace App\Api\Internal;

use App\Achievements\AchievementService;
use App\Stats\StatsQueryService;
use App\UserProgress\UserProgressService;

final class AchievementsApiPayloadBuilder
{
    public static function build(\mysqli $conn, int $userId): array
    {
        $service = new AchievementService($conn);
        $statsQueryService = new StatsQueryService($conn);
        $userProgressService = new UserProgressService($conn);
        $pageData = $service->getAchievementsPageData($userId);
        $progressSummary = $userProgressService->refreshUserProgressSummary($userId, $pageData['achievements'] ?? null);

        $pageData['hero']['level'] = (int) ($progressSummary['level'] ?? ($pageData['hero']['level'] ?? 1));
        $pageData['hero']['total_xp'] = (int) ($progressSummary['total_xp'] ?? ($pageData['hero']['total_xp'] ?? 0));
        $pageData['hero']['xp_progress_percent'] = (int) ($progressSummary['xp_progress_percent'] ?? ($pageData['hero']['xp_progress_percent'] ?? 0));
        $pageData['hero']['xp_to_next_level'] = (int) ($progressSummary['xp_to_next_level'] ?? ($pageData['hero']['xp_to_next_level'] ?? 0));
        $pageData['hero']['xp_needed_for_level'] = (int) ($progressSummary['xp_needed_for_level'] ?? ($pageData['hero']['xp_needed_for_level'] ?? 1));
        $pageData['hero']['completion_xp'] = (int) ($progressSummary['completion_xp'] ?? 0);
        $pageData['hero']['achievement_xp'] = (int) ($progressSummary['achievement_xp'] ?? 0);
        $pageData['hero']['next_level_reward'] = $progressSummary['next_level_reward'] ?? null;
        $pageData['stats']['total_habits'] = $statsQueryService->getTotalHabits($userId);
        $pageData['stats']['total_badges_unlocked'] = (int) ($progressSummary['total_badges_unlocked'] ?? 0);

        return [
            'success' => true,
            'data' => $pageData,
        ];
    }
}

<?php

namespace App\Api\Internal;

use App\Achievements\AchievementService;
use App\Stats\StatsQueryService;

final class AchievementsApiPayloadBuilder
{
    public static function build(\mysqli $conn, int $userId): array
    {
        $service = new AchievementService($conn);
        $statsQueryService = new StatsQueryService($conn);
        $pageData = $service->getAchievementsPageData($userId);
        $pageData['stats']['total_habits'] = $statsQueryService->getTotalHabits($userId);

        return [
            'success' => true,
            'data' => $pageData,
        ];
    }
}

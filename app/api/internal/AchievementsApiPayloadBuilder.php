<?php

namespace App\Api\Internal;

use App\Achievements\AchievementService;

final class AchievementsApiPayloadBuilder
{
    public static function build(\mysqli $conn, int $userId): array
    {
        $service = new AchievementService($conn);

        return [
            'success' => true,
            'data' => $service->getAchievementsPageData($userId),
        ];
    }
}

<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Api\AchievementsApiGetActionHandler;
use App\Api\Internal\AchievementsApiPayloadBuilder;

function buildAchievementsApiResponse(mysqli $conn, int $userId): array
{
    return AchievementsApiPayloadBuilder::build($conn, $userId);
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    actionRunApi(static function () use ($conn): void {
        $handler = new AchievementsApiGetActionHandler();
        actionApplyResponse($handler->handle($conn, $_SESSION));
    });
}

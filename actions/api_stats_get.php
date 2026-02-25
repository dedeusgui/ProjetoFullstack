<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Api\ApiQueryParamNormalizer;
use App\Actions\Api\StatsApiGetActionHandler;
use App\Api\Internal\StatsApiPayloadBuilder;

function buildStatsApiResponse(mysqli $conn, int $userId, string $view = 'dashboard'): array
{
    return StatsApiPayloadBuilder::build($conn, $userId, $view);
}

function resolveStatsApiView($view): string
{
    return ApiQueryParamNormalizer::normalizeStatsView($view);
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    actionRunApi(static function () use ($conn): void {
        $handler = new StatsApiGetActionHandler();
        actionApplyResponse($handler->handle($conn, $_GET, $_SERVER, $_SESSION));
    });
}

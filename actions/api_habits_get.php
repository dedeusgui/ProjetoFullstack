<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Api\ApiQueryParamNormalizer;
use App\Actions\Api\HabitsApiGetActionHandler;
use App\Api\Internal\HabitsApiPayloadBuilder;

function buildHabitsApiResponse(mysqli $conn, int $userId, string $scope = 'all'): array
{
    return HabitsApiPayloadBuilder::build($conn, $userId, $scope);
}

function resolveHabitsApiScope($scope): string
{
    return ApiQueryParamNormalizer::normalizeHabitsScope($scope);
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    actionRunApi(static function () use ($conn): void {
        $handler = new HabitsApiGetActionHandler();
        actionApplyResponse($handler->handle($conn, $_GET, $_SERVER, $_SESSION));
    });
}

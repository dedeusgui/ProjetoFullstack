<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Api\Internal\HabitsApiPayloadBuilder;

function buildHabitsApiResponse(mysqli $conn, int $userId, string $scope = 'all'): array
{
    return HabitsApiPayloadBuilder::build($conn, $userId, $scope);
}

function resolveHabitsApiScope($scope): string
{
    $scope = is_string($scope) ? trim($scope) : '';
    $allowedScopes = ['all', 'today', 'page'];

    return in_array($scope, $allowedScopes, true) ? $scope : 'all';
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    actionRunApi(static function () use ($conn): void {
        if (!isUserLoggedIn()) {
            actionJsonError('Usuario nao autenticado.', 401, 'unauthorized');
        }

        $userId = (int) getAuthenticatedUserId();
        $scope = resolveHabitsApiScope($_GET['scope'] ?? 'all');

        actionJsonResponse(buildHabitsApiResponse($conn, $userId, $scope));
    });
}

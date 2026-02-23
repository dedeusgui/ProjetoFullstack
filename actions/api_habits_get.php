<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Api\Internal\HabitsApiPayloadBuilder;

function buildHabitsApiResponse(mysqli $conn, int $userId, string $scope = 'all'): array
{
    return HabitsApiPayloadBuilder::build($conn, $userId, $scope);
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    actionRunApi(static function () use ($conn): void {
        if (!isUserLoggedIn()) {
            actionJsonError('UsuÃ¡rio nÃ£o autenticado.', 401, 'unauthorized');
        }

        $userId = (int) getAuthenticatedUserId();
        $scope = $_GET['scope'] ?? 'all';

        actionJsonResponse(buildHabitsApiResponse($conn, $userId, $scope));
    });
}

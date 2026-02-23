<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Api\Internal\StatsApiPayloadBuilder;

function buildStatsApiResponse(mysqli $conn, int $userId, string $view = 'dashboard'): array
{
    return StatsApiPayloadBuilder::build($conn, $userId, $view);
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    actionRunApi(static function () use ($conn): void {
        if (!isUserLoggedIn()) {
            actionJsonError('UsuÃ¡rio nÃ£o autenticado.', 401, 'unauthorized');
        }

        $userId = (int) getAuthenticatedUserId();
        $view = $_GET['view'] ?? 'dashboard';

        actionJsonResponse(buildStatsApiResponse($conn, $userId, $view));
    });
}

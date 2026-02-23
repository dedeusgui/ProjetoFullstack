<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Api\Internal\StatsApiPayloadBuilder;

function buildStatsApiResponse(mysqli $conn, int $userId, string $view = 'dashboard'): array
{
    return StatsApiPayloadBuilder::build($conn, $userId, $view);
}

function resolveStatsApiView($view): string
{
    $view = is_string($view) ? trim($view) : '';
    $allowedViews = ['dashboard', 'history'];

    return in_array($view, $allowedViews, true) ? $view : 'dashboard';
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    actionRunApi(static function () use ($conn): void {
        if (!isUserLoggedIn()) {
            actionJsonError('Usuario nao autenticado.', 401, 'unauthorized');
        }

        $userId = (int) getAuthenticatedUserId();
        $view = resolveStatsApiView($_GET['view'] ?? 'dashboard');

        actionJsonResponse(buildStatsApiResponse($conn, $userId, $view));
    });
}

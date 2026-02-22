<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Api\Internal\StatsApiPayloadBuilder;

function buildStatsApiResponse(mysqli $conn, int $userId, string $view = 'dashboard'): array
{
    return StatsApiPayloadBuilder::build($conn, $userId, $view);
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    header('Content-Type: application/json; charset=utf-8');

    if (!isUserLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não autenticado.'
        ]);
        exit;
    }

    $userId = (int) getAuthenticatedUserId();
    $view = $_GET['view'] ?? 'dashboard';

    echo json_encode(buildStatsApiResponse($conn, $userId, $view));
}

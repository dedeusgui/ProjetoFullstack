<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Api\Internal\HabitsApiPayloadBuilder;

function buildHabitsApiResponse(mysqli $conn, int $userId, string $scope = 'all'): array
{
    return HabitsApiPayloadBuilder::build($conn, $userId, $scope);
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
    $scope = $_GET['scope'] ?? 'all';

    echo json_encode(buildHabitsApiResponse($conn, $userId, $scope));
}

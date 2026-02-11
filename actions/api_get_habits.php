<?php

require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';

function buildHabitsApiResponse(mysqli $conn, int $userId, string $scope = 'all'): array
{
    $mapHabit = static function (array $habit): array {
        return [
            'id' => (int) $habit['id'],
            'name' => $habit['title'],
            'description' => $habit['description'] ?? '',
            'category' => $habit['category_name'] ?? 'Sem categoria',
            'time' => mapTimeOfDayReverse($habit['time_of_day'] ?? 'anytime'),
            'color' => $habit['color'] ?? '#4a74ff',
            'streak' => (int) ($habit['current_streak'] ?? 0),
            'completed_today' => (bool) ($habit['completed_today'] ?? false),
            'created_at' => $habit['created_at'] ?? null
        ];
    };

    $response = [
        'success' => true,
        'scope' => $scope,
        'generated_at' => date('c')
    ];

    if ($scope === 'today') {
        $todayHabitsRaw = getTodayHabits($conn, $userId);
        $todayHabits = array_map($mapHabit, $todayHabitsRaw);

        $response['data'] = [
            'count' => count($todayHabits),
            'habits' => $todayHabits
        ];

        return $response;
    }

    $habitsRaw = getUserHabits($conn, $userId);
    $habits = array_map($mapHabit, $habitsRaw);

    $response['data'] = [
        'count' => count($habits),
        'habits' => $habits
    ];

    return $response;
}

if (!defined('DOITLY_INTERNAL_API_CALL')) {
    header('Content-Type: application/json; charset=utf-8');

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não autenticado.'
        ]);
        exit;
    }

    $userId = getUserId();
    $scope = $_GET['scope'] ?? 'all';

    echo json_encode(buildHabitsApiResponse($conn, (int) $userId, $scope));
}

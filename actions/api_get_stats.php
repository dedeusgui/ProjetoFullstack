<?php

require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';

function buildStatsApiResponse(mysqli $conn, int $userId, string $view = 'dashboard'): array
{
    $totalHabits = getTotalHabits($conn, $userId);
    $completedToday = getCompletedToday($conn, $userId);
    $completionRate = getCompletionRate($conn, $userId);
    $currentStreak = getCurrentStreak($conn, $userId);

    $dashboardData = [
        'stats' => [
            'total_habits' => $totalHabits,
            'completed_today' => $completedToday,
            'completion_rate' => $completionRate,
            'current_streak' => $currentStreak
        ],
        'today_habits' => array_map(static function (array $habit): array {
            return [
                'id' => (int) $habit['id'],
                'name' => $habit['title'],
                'category' => $habit['category_name'] ?? 'Sem categoria',
                'time' => mapTimeOfDayReverse($habit['time_of_day'] ?? 'anytime'),
                'completed' => (bool) ($habit['completed_today'] ?? false)
            ];
        }, getTodayHabits($conn, $userId)),
        'weekly_data' => getMonthlyData($conn, $userId, 7)
    ];

    if ($view === 'history') {
        $totalCompletions = getTotalCompletions($conn, $userId);
        $bestStreak = getBestStreak($conn, $userId);

        $achievements = getUserAchievements($conn, $userId);

    $historyData = [
            'stats' => [
                'total_habits' => $totalHabits,
                'total_completions' => $totalCompletions,
                'current_streak' => $currentStreak,
                'best_streak' => $bestStreak,
                'completion_rate' => $completionRate,
                'active_days' => (int) round(($completionRate / 100) * 30),
                'total_days' => 30
            ],
            'monthly_data' => getMonthlyData($conn, $userId, 30),
            'category_stats' => getCategoryStats($conn, $userId),
            'recent_history' => getRecentHistory($conn, $userId, 10),
            'achievements' => $achievements
        ];

        return [
            'success' => true,
            'view' => 'history',
            'generated_at' => date('c'),
            'data' => $historyData
        ];
    }

    return [
        'success' => true,
        'view' => 'dashboard',
        'generated_at' => date('c'),
        'data' => $dashboardData
    ];
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
    $view = $_GET['view'] ?? 'dashboard';

    echo json_encode(buildStatsApiResponse($conn, (int) $userId, $view));
}

<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';

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

    $achievements = [
        [
            'id' => 1,
            'name' => 'Primeiro Passo',
            'description' => 'Complete seu primeiro hábito',
            'icon' => 'bi-flag',
            'unlocked' => $totalCompletions >= 1,
            'date' => $totalCompletions >= 1 ? 'Desbloqueado' : null,
            'progress' => min(100, ($totalCompletions / 1) * 100)
        ],
        [
            'id' => 2,
            'name' => 'Guerreiro Semanal',
            'description' => 'Mantenha um streak de 7 dias',
            'icon' => 'bi-fire',
            'unlocked' => $bestStreak >= 7,
            'date' => $bestStreak >= 7 ? 'Desbloqueado' : null,
            'progress' => min(100, ($currentStreak / 7) * 100)
        ],
        [
            'id' => 3,
            'name' => 'Clube dos 100',
            'description' => 'Complete 100 hábitos',
            'icon' => 'bi-star',
            'unlocked' => $totalCompletions >= 100,
            'date' => $totalCompletions >= 100 ? 'Desbloqueado' : null,
            'progress' => min(100, ($totalCompletions / 100) * 100)
        ],
        [
            'id' => 4,
            'name' => 'Mestre do Mês',
            'description' => 'Mantenha um streak de 30 dias',
            'icon' => 'bi-trophy',
            'unlocked' => $bestStreak >= 30,
            'date' => $bestStreak >= 30 ? 'Desbloqueado' : null,
            'progress' => min(100, ($currentStreak / 30) * 100)
        ],
        [
            'id' => 5,
            'name' => 'Imparável',
            'description' => 'Mantenha um streak de 100 dias',
            'icon' => 'bi-rocket',
            'unlocked' => $bestStreak >= 100,
            'date' => $bestStreak >= 100 ? 'Desbloqueado' : null,
            'progress' => min(100, ($currentStreak / 100) * 100)
        ]
    ];

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

    echo json_encode([
        'success' => true,
        'view' => 'history',
        'generated_at' => date('c'),
        'data' => $historyData
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'view' => 'dashboard',
    'generated_at' => date('c'),
    'data' => $dashboardData
]);

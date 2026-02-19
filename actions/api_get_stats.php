<?php

require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';
require_once '../app/recommendation/BehaviorAnalyzer.php';
require_once '../app/recommendation/TrendAnalyzer.php';
require_once '../app/recommendation/ScoreEngine.php';
require_once '../app/recommendation/RecommendationEngine.php';

function getLatestRecommendationSnapshot(mysqli $conn, int $userId): ?array
{
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_recommendations'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return null;
    }

    $stmt = $conn->prepare("SELECT score, trend, risk_level, recommendation_text, recommendation_payload, created_at
        FROM user_recommendations
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        return null;
    }

    return [
        'score' => (int) $row['score'],
        'trend' => $row['trend'],
        'risk_level' => $row['risk_level'],
        'recommendation_text' => $row['recommendation_text'],
        'recommendation_payload' => json_decode($row['recommendation_payload'] ?? '{}', true),
        'created_at' => $row['created_at']
    ];
}

function saveRecommendationSnapshot(mysqli $conn, int $userId, array $scoreData, array $trendData, array $recommendation): void
{
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_recommendations'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return;
    }

    $text = $recommendation['insight_text'] ?? '';
    $payload = json_encode($recommendation, JSON_UNESCAPED_UNICODE);
    $score = (int) ($scoreData['score'] ?? 0);
    $trend = $trendData['trend'] ?? 'neutral';
    $risk = $scoreData['risk_level'] ?? 'stable';

    $stmt = $conn->prepare("INSERT INTO user_recommendations (user_id, score, trend, risk_level, recommendation_text, recommendation_payload)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iissss', $userId, $score, $trend, $risk, $text, $payload);
    $stmt->execute();
}

function buildAdaptiveRecommendation(mysqli $conn, int $userId): array
{
    $latest = getLatestRecommendationSnapshot($conn, $userId);

    if ($latest !== null) {
        $createdAt = strtotime((string) $latest['created_at']);
        if ($createdAt !== false && (time() - $createdAt) < 86400) {
            return [
                'score' => $latest['score'],
                'trend' => $latest['trend'],
                'risk_level' => $latest['risk_level'],
                'generated_at' => date('c', $createdAt),
                'recommendation' => $latest['recommendation_payload'] ?: [
                    'insight_text' => $latest['recommendation_text']
                ],
                'source' => 'cached'
            ];
        }
    }

    $behaviorData = BehaviorAnalyzer::analyze($conn, $userId);
    $trendData = TrendAnalyzer::detect($behaviorData);
    $scoreData = ScoreEngine::calculate($behaviorData, $trendData);
    $recommendation = RecommendationEngine::generate($scoreData, $trendData, $behaviorData);

    saveRecommendationSnapshot($conn, $userId, $scoreData, $trendData, $recommendation);

    return [
        'score' => $scoreData['score'],
        'trend' => $trendData['trend'],
        'risk_level' => $scoreData['risk_level'],
        'generated_at' => date('c'),
        'behavior' => $behaviorData,
        'recommendation' => $recommendation,
        'reasons' => $scoreData['reasons'],
        'source' => 'fresh'
    ];
}

function buildStatsApiResponse(mysqli $conn, int $userId, string $view = 'dashboard'): array
{
    $totalHabits = getTotalHabits($conn, $userId);
    $completedToday = getCompletedToday($conn, $userId);
    $completionRate = getCompletionRate($conn, $userId);
    $completionTrend = getCompletionTrend($conn, $userId, 7);
    $overallCompletion = getCompletionWindowSummary($conn, $userId);
    $currentStreak = getCurrentStreak($conn, $userId);

    $dashboardData = [
        'stats' => [
            'total_habits' => $totalHabits,
            'completed_today' => $completedToday,
            'completion_rate' => $completionRate,
            'completion_change' => $completionTrend,
            'active_days' => getActiveDays($conn, $userId),
            'tracked_days' => (int) ($overallCompletion['days_analyzed'] ?? 0),
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
        }, getTodayHabits($conn, $userId, getUserTodayDate($conn, $userId))),
        'weekly_data' => getMonthlyData($conn, $userId, 7),
        'adaptive_recommendation' => buildAdaptiveRecommendation($conn, $userId)
    ];

    if ($view === 'history') {
        $totalCompletions = getTotalCompletions($conn, $userId);
        $bestStreak = getBestStreak($conn, $userId);

        $achievements = getUserAchievements($conn, $userId);

        $userCreatedAt = getUserCreatedAt($conn, $userId);

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
            'recent_history' => getRecentHistory($conn, $userId, 10, $userCreatedAt),
            'achievements' => $achievements,
            'adaptive_recommendation' => buildAdaptiveRecommendation($conn, $userId)
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

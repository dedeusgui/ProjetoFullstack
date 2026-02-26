<?php

namespace App\Api\Internal;

use App\Recommendation\BehaviorAnalyzer;
use App\Recommendation\RecommendationEngine;
use App\Recommendation\ScoreEngine;
use App\Recommendation\TrendAnalyzer;
use App\Stats\StatsQueryService;

class StatsApiPayloadBuilder
{
    public static function build(\mysqli $conn, int $userId, string $view = 'dashboard'): array
    {
        $view = self::normalizeView($view);
        $statsQueryService = new StatsQueryService($conn);
        $userTodayDate = $statsQueryService->getUserTodayDate($userId);
        $totalHabits = $statsQueryService->getTotalHabits($userId);
        $completedToday = $statsQueryService->getCompletedToday($userId, $userTodayDate);
        $completionRate = $statsQueryService->getCompletionRate($userId);
        $completionTrend = $statsQueryService->getCompletionTrend($userId, 7);
        $overallCompletion = $statsQueryService->getCompletionWindowSummary($userId);
        $currentStreak = $statsQueryService->getCurrentStreak($userId);

        $todayHabitsRaw = $statsQueryService->getTodayHabits($userId, $userTodayDate);
        $scheduledToday = count($todayHabitsRaw);
        $todayRate = $scheduledToday > 0 ? (int) round($completedToday / $scheduledToday * 100) : 0;

        $dashboardData = [
            'stats' => [
                'total_habits' => $totalHabits,
                'completed_today' => $completedToday,
                'completion_rate' => $completionRate,
                'today_rate' => $todayRate,
                'completion_change' => $completionTrend,
                'active_days' => $statsQueryService->getActiveDays($userId),
                'tracked_days' => (int) ($overallCompletion['days_analyzed'] ?? 0),
                'current_streak' => $currentStreak,
                'best_streak' => $statsQueryService->getBestStreak($userId),
                'total_completions' => $statsQueryService->getTotalCompletions($userId),
            ],
            'today_habits' => array_map(static function (array $habit): array {
                return [
                    'id' => (int) $habit['id'],
                    'name' => $habit['title'],
                    'category' => $habit['category_name'] ?? 'Sem categoria',
                    'time' => mapTimeOfDayReverse($habit['time_of_day'] ?? 'anytime'),
                    'goal_type' => $habit['goal_type'] ?? 'completion',
                    'goal_value' => (int) ($habit['goal_value'] ?? 1),
                    'goal_unit' => $habit['goal_unit'] ?? '',
                    'completed' => (bool) ($habit['completed_today'] ?? false)
                ];
            }, $todayHabitsRaw),
            'weekly_data' => $statsQueryService->getMonthlyData($userId, 7),
            'adaptive_recommendation' => self::buildAdaptiveRecommendation($conn, $userId, $userTodayDate)
        ];

        if ($view === 'history') {
            $totalCompletions = $statsQueryService->getTotalCompletions($userId);
            $bestStreak = $statsQueryService->getBestStreak($userId);
            $userCreatedAt = $statsQueryService->getUserCreatedAt($userId);

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
                'monthly_data' => $statsQueryService->getMonthlyData($userId, 30),
                'category_stats' => $statsQueryService->getCategoryStats($userId),
                'recent_history' => $statsQueryService->getRecentHistory($userId, 10, $userCreatedAt),
                'adaptive_recommendation' => self::buildAdaptiveRecommendation($conn, $userId, $userTodayDate)
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

    private static function normalizeView(string $view): string
    {
        $view = trim($view);
        $allowedViews = ['dashboard', 'history'];

        return in_array($view, $allowedViews, true) ? $view : 'dashboard';
    }

    private static function buildAdaptiveRecommendation(\mysqli $conn, int $userId, ?string $referenceDate = null): array
    {
        $latest = self::getLatestRecommendationSnapshot($conn, $userId);

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

        $behaviorData = BehaviorAnalyzer::analyze($conn, $userId, $referenceDate);
        $trendData = TrendAnalyzer::detect($behaviorData);
        $scoreData = ScoreEngine::calculate($behaviorData, $trendData);
        $recommendation = RecommendationEngine::generate($scoreData, $trendData, $behaviorData);

        self::saveRecommendationSnapshot($conn, $userId, $scoreData, $trendData, $recommendation);

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

    private static function getLatestRecommendationSnapshot(\mysqli $conn, int $userId): ?array
    {
        if (!self::hasUserRecommendationsTable($conn)) {
            return null;
        }

        $stmt = $conn->prepare("SELECT score, trend, risk_level, recommendation_text, recommendation_payload, created_at
            FROM user_recommendations
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

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

    private static function saveRecommendationSnapshot(\mysqli $conn, int $userId, array $scoreData, array $trendData, array $recommendation): void
    {
        if (!self::hasUserRecommendationsTable($conn)) {
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

    private static function hasUserRecommendationsTable(\mysqli $conn): bool
    {
        static $hasTable = null;

        if ($hasTable !== null) {
            return $hasTable;
        }

        $tableCheck = $conn->query("SHOW TABLES LIKE 'user_recommendations'");
        $hasTable = (bool) ($tableCheck && $tableCheck->num_rows > 0);

        return $hasTable;
    }
}

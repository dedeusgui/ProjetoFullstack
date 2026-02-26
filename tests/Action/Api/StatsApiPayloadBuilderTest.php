<?php

declare(strict_types=1);

namespace Tests\Action\Api;

use App\Api\Internal\StatsApiPayloadBuilder;
use Tests\Support\ActionTestCase;

final class StatsApiPayloadBuilderTest extends ActionTestCase
{
    public function testBuildDashboardPayloadIncludesExpectedKeysAndComputedTodayRate(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $today = $this->today();
        $habitA = $this->fixtures->createHabit($userId, [
            'title' => 'Habit A',
            'frequency' => 'daily',
            'time_of_day' => 'morning',
        ]);
        $habitB = $this->fixtures->createHabit($userId, [
            'title' => 'Habit B',
            'frequency' => 'daily',
            'time_of_day' => 'afternoon',
        ]);

        $this->fixtures->createCompletion($habitA, $userId, ['completion_date' => $today]);
        $this->db()->execute('UPDATE habits SET category_id = NULL, time_of_day = NULL, goal_value = NULL, goal_unit = NULL WHERE id = ' . (int) $habitB);

        $payload = StatsApiPayloadBuilder::build($this->conn(), $userId, 'dashboard');

        self::assertTrue($payload['success'] ?? false);
        self::assertSame('dashboard', $payload['view'] ?? null);
        self::assertArrayHasKey('generated_at', $payload);
        self::assertNotFalse(strtotime((string) $payload['generated_at']));

        $data = $payload['data'] ?? [];
        self::assertArrayHasKey('stats', $data);
        self::assertArrayHasKey('today_habits', $data);
        self::assertArrayHasKey('weekly_data', $data);
        self::assertArrayHasKey('adaptive_recommendation', $data);
        self::assertSame(50, (int) (($data['stats']['today_rate'] ?? -1)));

        $habitBPayload = $this->findById($data['today_habits'] ?? [], $habitB);
        self::assertNotNull($habitBPayload);
        self::assertSame('Sem categoria', $habitBPayload['category'] ?? null);
        self::assertSame('Qualquer', $habitBPayload['time'] ?? null);
        self::assertSame(1, $habitBPayload['goal_value'] ?? null);
        self::assertSame('', $habitBPayload['goal_unit'] ?? null);

        self::assertSame('fresh', $data['adaptive_recommendation']['source'] ?? null);
    }

    public function testBuildDashboardPayloadReturnsZeroTodayRateWhenNoHabitsScheduledToday(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $todayWeekday = (int) date('w', strtotime($this->today()));
        $otherWeekday = ($todayWeekday + 1) % 7;
        $this->fixtures->createHabit($userId, [
            'frequency' => 'weekly',
            'target_days' => json_encode([$otherWeekday]),
        ]);

        $payload = StatsApiPayloadBuilder::build($this->conn(), $userId, 'dashboard');

        self::assertSame(0, (int) (($payload['data']['stats']['today_rate'] ?? -1)));
    }

    public function testBuildHistoryPayloadIncludesExpectedShape(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);

        $payload = StatsApiPayloadBuilder::build($this->conn(), $userId, 'history');

        self::assertTrue($payload['success'] ?? false);
        self::assertSame('history', $payload['view'] ?? null);

        $data = $payload['data'] ?? [];
        self::assertArrayHasKey('stats', $data);
        self::assertArrayHasKey('monthly_data', $data);
        self::assertArrayHasKey('category_stats', $data);
        self::assertArrayHasKey('recent_history', $data);
        self::assertArrayNotHasKey('achievements', $data);
        self::assertArrayHasKey('adaptive_recommendation', $data);
    }

    public function testInvalidViewFallsBackToDashboard(): void
    {
        $userId = $this->fixtures->createUser();

        $payload = StatsApiPayloadBuilder::build($this->conn(), $userId, 'invalid-view');

        self::assertSame('dashboard', $payload['view'] ?? null);
    }

    public function testUsesCachedRecommendationSnapshotWhenRecent(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);

        $stmt = $this->conn()->prepare(
            'INSERT INTO user_recommendations (user_id, score, trend, risk_level, recommendation_text, recommendation_payload)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $score = 88;
        $trend = 'positive';
        $risk = 'high_performer';
        $text = 'Cached insight';
        $json = json_encode(['insight_text' => 'Cached insight', 'priority' => 'low'], JSON_UNESCAPED_UNICODE);
        $stmt->bind_param('iissss', $userId, $score, $trend, $risk, $text, $json);
        $stmt->execute();

        $payload = StatsApiPayloadBuilder::build($this->conn(), $userId, 'dashboard');
        $recommendation = $payload['data']['adaptive_recommendation'] ?? [];

        self::assertSame('cached', $recommendation['source'] ?? null);
        self::assertSame($score, $recommendation['score'] ?? null);
        self::assertSame($trend, $recommendation['trend'] ?? null);
        self::assertSame($risk, $recommendation['risk_level'] ?? null);
        self::assertArrayHasKey('recommendation', $recommendation);
    }

    private function today(): string
    {
        return date('Y-m-d');
    }

    private function findById(array $rows, int $id): ?array
    {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }
}

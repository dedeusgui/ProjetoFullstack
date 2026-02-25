<?php

declare(strict_types=1);

namespace Tests\Unit\Recommendation;

use App\Recommendation\ScoreEngine;
use PHPUnit\Framework\TestCase;

final class ScoreEngineTest extends TestCase
{
    public function testCalculatesHighPerformerScore(): void
    {
        $result = ScoreEngine::calculate([
            'completion_rate_30' => 90,
            'current_streak' => 10,
            'consecutive_failures' => 0,
        ], [
            'trend' => 'positive',
        ]);

        self::assertSame(5, $result['score']);
        self::assertSame('high_performer', $result['risk_level']);
        self::assertNotEmpty($result['reasons']);
    }

    public function testConsecutiveFailuresForceAtRisk(): void
    {
        $result = ScoreEngine::calculate([
            'completion_rate_30' => 85,
            'current_streak' => 8,
            'consecutive_failures' => 3,
        ], [
            'trend' => 'positive',
        ]);

        self::assertSame('at_risk', $result['risk_level']);
        self::assertLessThanOrEqual(2, $result['score']);
    }

    public function testNeutralInputsProduceAttentionOrStableBand(): void
    {
        $result = ScoreEngine::calculate([
            'completion_rate_30' => 50,
            'current_streak' => 0,
            'consecutive_failures' => 0,
        ], [
            'trend' => 'neutral',
        ]);

        self::assertContains($result['risk_level'], ['attention', 'stable']);
    }
}

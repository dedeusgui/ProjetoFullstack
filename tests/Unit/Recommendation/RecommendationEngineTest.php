<?php

declare(strict_types=1);

namespace Tests\Unit\Recommendation;

use App\Recommendation\RecommendationEngine;
use PHPUnit\Framework\TestCase;

final class RecommendationEngineTest extends TestCase
{
    public function testHighPerformerRecommendationContainsLowPriority(): void
    {
        $result = RecommendationEngine::generate(
            ['risk_level' => 'high_performer'],
            ['trend' => 'positive'],
            ['consecutive_failures' => 0]
        );

        self::assertSame('low', $result['priority'] ?? null);
        self::assertArrayHasKey('actions', $result);
        self::assertCount(3, $result['actions'] ?? []);
    }

    public function testStableRecommendationContainsMediumPriority(): void
    {
        $result = RecommendationEngine::generate(
            ['risk_level' => 'stable'],
            ['trend' => 'neutral'],
            []
        );

        self::assertSame('medium', $result['priority'] ?? null);
    }

    public function testAttentionRecommendationContainsHighPriority(): void
    {
        $result = RecommendationEngine::generate(
            ['risk_level' => 'attention'],
            ['trend' => 'negative'],
            []
        );

        self::assertSame('high', $result['priority'] ?? null);
    }

    public function testAtRiskRecommendationIncludesContext(): void
    {
        $result = RecommendationEngine::generate(
            ['risk_level' => 'at_risk'],
            ['trend' => 'negative'],
            ['consecutive_failures' => 4]
        );

        self::assertSame('urgent', $result['priority'] ?? null);
        self::assertSame('at_risk', $result['status'] ?? null);
        self::assertSame(4, $result['context']['consecutive_failures'] ?? null);
        self::assertSame('negative', $result['context']['trend'] ?? null);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Recommendation;

use App\Recommendation\TrendAnalyzer;
use PHPUnit\Framework\TestCase;

final class TrendAnalyzerTest extends TestCase
{
    public function testDetectsPositiveTrendAndConsistency(): void
    {
        $result = TrendAnalyzer::detect([
            'avg_daily_7' => 3.2,
            'avg_daily_30' => 2.8,
            'total_habits' => 5,
            'consecutive_failures' => 0,
            'daily_series_30' => [
                ['completed' => 1],
                ['completed' => 0],
                ['completed' => 2],
                ['completed' => 3],
            ],
        ]);

        self::assertSame('positive', $result['trend']);
        self::assertSame(75.0, $result['consistency']);
        self::assertSame(0.4, $result['delta']);
    }

    public function testPositiveTrendCanBeDowngradedToNeutral(): void
    {
        $result = TrendAnalyzer::detect([
            'avg_daily_7' => 2.0,
            'avg_daily_30' => 1.0,
            'total_habits' => 4,
            'consecutive_failures' => 2,
            'daily_series_30' => [
                ['completed' => 1],
                ['completed' => 0],
                ['completed' => 0],
            ],
        ]);

        self::assertSame('neutral', $result['trend']);
    }

    public function testEmptySeriesProducesZeroConsistency(): void
    {
        $result = TrendAnalyzer::detect([
            'avg_daily_7' => 1.0,
            'avg_daily_30' => 1.0,
            'daily_series_30' => [],
        ]);

        self::assertSame('neutral', $result['trend']);
        self::assertSame(0.0, $result['consistency']);
        self::assertSame(0.0, $result['delta']);
    }
}

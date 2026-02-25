<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Api;

use App\Actions\Api\ApiQueryParamNormalizer;
use PHPUnit\Framework\TestCase;

final class ApiQueryParamNormalizerTest extends TestCase
{
    public function testNormalizesStatsView(): void
    {
        self::assertSame('dashboard', ApiQueryParamNormalizer::normalizeStatsView('dashboard'));
        self::assertSame('history', ApiQueryParamNormalizer::normalizeStatsView(' history '));
        self::assertSame('dashboard', ApiQueryParamNormalizer::normalizeStatsView('invalid'));
        self::assertSame('dashboard', ApiQueryParamNormalizer::normalizeStatsView(null));
        self::assertSame('dashboard', ApiQueryParamNormalizer::normalizeStatsView(['history']));
    }

    public function testNormalizesHabitsScope(): void
    {
        self::assertSame('all', ApiQueryParamNormalizer::normalizeHabitsScope('all'));
        self::assertSame('today', ApiQueryParamNormalizer::normalizeHabitsScope(' today '));
        self::assertSame('page', ApiQueryParamNormalizer::normalizeHabitsScope('page'));
        self::assertSame('all', ApiQueryParamNormalizer::normalizeHabitsScope('invalid'));
        self::assertSame('all', ApiQueryParamNormalizer::normalizeHabitsScope(123));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Habits;

use App\Actions\Habits\HabitRefererRedirectResolver;
use PHPUnit\Framework\TestCase;

final class HabitRefererRedirectResolverTest extends TestCase
{
    public function testReturnsValidSameHostRefererPath(): void
    {
        $path = HabitRefererRedirectResolver::resolve([
            'HTTP_HOST' => 'localhost',
            'HTTP_REFERER' => 'http://localhost/public/habits.php?page=2',
        ]);

        self::assertSame('/public/habits.php?page=2', $path);
    }

    public function testRejectsExternalHostReferer(): void
    {
        $path = HabitRefererRedirectResolver::resolve([
            'HTTP_HOST' => 'localhost',
            'HTTP_REFERER' => 'http://evil.test/public/habits.php',
        ]);

        self::assertSame('../public/habits.php', $path);
    }

    public function testRejectsMalformedRefererPath(): void
    {
        $path = HabitRefererRedirectResolver::resolve([
            'HTTP_HOST' => 'localhost',
            'HTTP_REFERER' => 'http://localhost',
        ]);

        self::assertSame('../public/habits.php', $path);
    }
}

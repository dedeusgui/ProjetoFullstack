<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\DateFormatter;
use PHPUnit\Framework\TestCase;

final class DateFormatterTest extends TestCase
{
    public function testFormatsValidDateToBrPattern(): void
    {
        self::assertSame('10/02/2026', DateFormatter::formatBr('2026-02-10'));
    }

    public function testReturnsSemDataForEmptyInput(): void
    {
        self::assertSame('Sem data', DateFormatter::formatBr(null));
        self::assertSame('Sem data', DateFormatter::formatBr(''));
    }

    public function testReturnsOriginalValueWhenDateIsInvalid(): void
    {
        self::assertSame('not-a-date', DateFormatter::formatBr('not-a-date'));
    }
}

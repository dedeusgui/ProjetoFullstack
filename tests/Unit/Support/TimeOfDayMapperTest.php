<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\TimeOfDayMapper;
use PHPUnit\Framework\TestCase;

final class TimeOfDayMapperTest extends TestCase
{
    public function testToDatabaseMapsKnownDisplayValues(): void
    {
        self::assertSame('afternoon', TimeOfDayMapper::toDatabase('Tarde'));
        self::assertSame('evening', TimeOfDayMapper::toDatabase('Noite'));
    }

    public function testToDatabaseFallsBackToAnytime(): void
    {
        self::assertSame('anytime', TimeOfDayMapper::toDatabase('unknown'));
    }

    public function testToDisplayMapsKnownValuesAndFallback(): void
    {
        self::assertSame('Tarde', TimeOfDayMapper::toDisplay('afternoon'));
        self::assertSame('Qualquer', TimeOfDayMapper::toDisplay('anytime'));
        self::assertSame('Qualquer', TimeOfDayMapper::toDisplay('invalid'));
    }
}

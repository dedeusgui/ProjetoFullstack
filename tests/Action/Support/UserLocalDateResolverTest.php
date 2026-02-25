<?php

declare(strict_types=1);

namespace Tests\Action\Support;

use App\Support\UserLocalDateResolver;
use Tests\Support\ActionTestCase;

final class UserLocalDateResolverTest extends ActionTestCase
{
    public function testReturnsTodayDateForUserTimezone(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $resolver = new UserLocalDateResolver($this->conn());

        $date = $resolver->getTodayDateForUser($userId);

        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
        self::assertSame((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d'), $date);
    }

    public function testFallsBackToDefaultTimezoneWhenUserTimezoneInvalid(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'Invalid/Timezone']);
        $resolver = new UserLocalDateResolver($this->conn());

        $date = $resolver->getTodayDateForUser($userId);

        self::assertSame((new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d'), $date);
    }

    public function testUsesCachedDatePerUserWithinSameResolverInstance(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $resolver = new UserLocalDateResolver($this->conn());

        $first = $resolver->getTodayDateForUser($userId);
        $this->db()->execute("UPDATE users SET timezone = 'Invalid/Timezone' WHERE id = " . (int) $userId);
        $second = $resolver->getTodayDateForUser($userId);

        self::assertSame($first, $second);
    }
}

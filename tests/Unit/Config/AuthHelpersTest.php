<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

final class AuthHelpersTest extends TestCase
{
    private array $sessionBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionBackup = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            @session_destroy();
        }

        parent::tearDown();
    }

    public function testAuthSessionHelpersReadAuthenticationState(): void
    {
        self::assertFalse(isUserLoggedIn());
        self::assertNull(getAuthenticatedUserId());

        $_SESSION['user_id'] = '42';

        self::assertTrue(isUserLoggedIn());
        self::assertSame(42, getAuthenticatedUserId());
    }

    public function testSignInUserSetsSessionFields(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        signInUser(7, 'Helper User', 'helper@example.com');

        self::assertSame(7, $_SESSION['user_id'] ?? null);
        self::assertSame('Helper User', $_SESSION['user_name'] ?? null);
        self::assertSame('helper@example.com', $_SESSION['user_email'] ?? null);
        self::assertIsInt($_SESSION['logged_in_at'] ?? null);
    }

    public function testGetUserInitialsSupportsSingleAndMultiPartNames(): void
    {
        self::assertSame('JD', getUserInitials('John Doe'));
        self::assertSame('MA', getUserInitials('Maria'));
        self::assertSame('JS', getUserInitials('Joana Silva'));
    }
}

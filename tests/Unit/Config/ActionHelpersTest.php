<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Support\RequestContext;
use PHPUnit\Framework\TestCase;

final class ActionHelpersTest extends TestCase
{
    private array $serverBackup;
    private array $postBackup;
    private array $sessionBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER ?? [];
        $this->postBackup = $_POST ?? [];
        $this->sessionBackup = $_SESSION ?? [];
        $_SERVER = ['REMOTE_ADDR' => '127.0.0.1'];
        $_POST = [];
        $_SESSION = [];
        $this->resetRequestId();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
        $_SESSION = $this->sessionBackup;
        $this->resetRequestId();
        parent::tearDown();
    }

    public function testActionResolveReturnPathAllowsWhitelistedPagesAndFallsBack(): void
    {
        $_POST['return_to'] = 'history.php';
        self::assertSame('../public/history.php', actionResolveReturnPath(['dashboard.php', 'history.php'], 'dashboard.php'));

        $_POST['return_to'] = 'admin.php';
        self::assertSame('../public/dashboard.php', actionResolveReturnPath(['dashboard.php', 'history.php'], 'dashboard.php'));
    }

    public function testCsrfTokenHelpersGenerateAndReuseToken(): void
    {
        ensureCsrfToken();
        $token1 = (string) ($_SESSION['csrf_token'] ?? '');
        $token2 = getCsrfToken();

        self::assertNotSame('', $token1);
        self::assertSame($token1, $token2);
    }

    public function testAuthFailureTrackingHelpersIncrementRateLimitAndClear(): void
    {
        $key = authAttemptKey();
        self::assertFalse(authIsRateLimited());

        for ($i = 0; $i < 5; $i++) {
            authRegisterFailure();
        }

        self::assertTrue(authIsRateLimited());
        self::assertArrayHasKey($key, $_SESSION);
        self::assertSame(5, (int) ($_SESSION[$key]['count'] ?? 0));

        authClearFailures();
        self::assertArrayNotHasKey($key, $_SESSION);
        self::assertFalse(authIsRateLimited());
    }

    public function testAuthIsRateLimitedResetsInvalidOrExpiredState(): void
    {
        $key = authAttemptKey();
        $_SESSION[$key] = 'invalid';
        self::assertFalse(authIsRateLimited());
        self::assertIsArray($_SESSION[$key] ?? null);

        $_SESSION[$key] = [
            'count' => 99,
            'first_attempt_at' => time() - 1000,
        ];
        self::assertFalse(authIsRateLimited(5, 100));
        self::assertSame(0, (int) ($_SESSION[$key]['count'] ?? -1));
    }

    public function testActionRequestContextWrappersDelegate(): void
    {
        RequestContext::setRequestId('wrap-id');

        self::assertSame('wrap-id', actionCurrentRequestId());
        self::assertFalse(actionIsJsonRequest());
    }

    private function resetRequestId(): void
    {
        $ref = new \ReflectionProperty(RequestContext::class, 'requestId');
        $ref->setValue(null, null);
    }
}

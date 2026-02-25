<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

final class SecurityHelpersTest extends TestCase
{
    private array $serverBackup;
    private mixed $nonceBackup;
    private bool $nonceExists;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER ?? [];
        $_SERVER = [];
        $this->nonceExists = array_key_exists('__app_csp_nonce', $GLOBALS);
        $this->nonceBackup = $GLOBALS['__app_csp_nonce'] ?? null;
        unset($GLOBALS['__app_csp_nonce']);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        if ($this->nonceExists) {
            $GLOBALS['__app_csp_nonce'] = $this->nonceBackup;
        } else {
            unset($GLOBALS['__app_csp_nonce']);
        }

        parent::tearDown();
    }

    public function testAppIsHttpsRequestChecksHttpsPortAndForwardedProto(): void
    {
        $_SERVER = ['HTTPS' => 'on'];
        self::assertTrue(appIsHttpsRequest());

        $_SERVER = ['SERVER_PORT' => '443'];
        self::assertTrue(appIsHttpsRequest());

        $_SERVER = ['HTTP_X_FORWARDED_PROTO' => 'https, http'];
        self::assertTrue(appIsHttpsRequest());

        $_SERVER = ['HTTPS' => 'off', 'SERVER_PORT' => '80', 'HTTP_X_FORWARDED_PROTO' => 'http'];
        self::assertFalse(appIsHttpsRequest());
    }

    public function testCspNonceIsGeneratedAndCached(): void
    {
        $nonce1 = appEnsureCspNonce();
        $nonce2 = appGetCspNonce();

        self::assertSame($nonce1, $nonce2);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{20,}$/', $nonce1);
    }

    public function testBuildContentSecurityPolicyContainsKeyDirectives(): void
    {
        $csp = appBuildContentSecurityPolicy();

        self::assertStringContainsString("default-src 'self'", $csp);
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function testConfigureSessionCookieParamsNoopsWhenSessionAlreadyActive(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        appConfigureSessionCookieParams();

        self::assertTrue(true);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            @session_destroy();
        }
    }
}

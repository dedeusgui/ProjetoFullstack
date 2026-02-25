<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Support\RequestContext;
use PHPUnit\Framework\TestCase;

final class ErrorHelpersTest extends TestCase
{
    private array $serverBackup;
    private array $sessionBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER ?? [];
        $this->sessionBackup = $_SESSION ?? [];
        $_SERVER = [];
        $_SESSION = [];
        $this->resetRequestId();
        RequestContext::setRequestId('req-test-1');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_SESSION = $this->sessionBackup;
        $this->resetRequestId();
        parent::tearDown();
    }

    public function testAppRequestIdAndJsonRequestHelpers(): void
    {
        self::assertSame('req-test-1', appRequestId());
        self::assertFalse(appIsJsonRequest(), 'CLI requests should not be treated as JSON requests.');
    }

    public function testAppBuildLogContextIncludesRequestAndUserContext(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/actions/test',
            'SCRIPT_NAME' => '/actions/test.php',
        ];
        $_SESSION['user_id'] = 77;

        $context = appBuildLogContext(['service' => 'unit-test']);

        self::assertSame('req-test-1', $context['request_id'] ?? null);
        self::assertSame('POST', $context['method'] ?? null);
        self::assertSame('/actions/test', $context['path'] ?? null);
        self::assertSame('/actions/test.php', $context['script'] ?? null);
        self::assertSame(77, $context['user_id'] ?? null);
        self::assertSame('unit-test', $context['service'] ?? null);
    }

    public function testAppStatusCodeForThrowableUsesGetStatusCodeWhenValid(): void
    {
        $exception = new class ('x') extends \RuntimeException {
            public function getStatusCode(): int
            {
                return 422;
            }
        };
        $invalid = new class ('x') extends \RuntimeException {
            public function getStatusCode(): int
            {
                return 200;
            }
        };

        self::assertSame(422, appStatusCodeForThrowable($exception));
        self::assertSame(500, appStatusCodeForThrowable($invalid));
        self::assertSame(500, appStatusCodeForThrowable(new \RuntimeException('x')));
    }

    public function testAppSendJsonErrorResponseWritesExpectedPayload(): void
    {
        RequestContext::setRequestId('req-json-1');

        ob_start();
        appSendJsonErrorResponse('Falhou', 500, 'boom');
        $output = (string) ob_get_clean();

        $decoded = json_decode($output, true);
        self::assertIsArray($decoded);
        self::assertFalse($decoded['success'] ?? true);
        self::assertSame('Falhou', $decoded['message'] ?? null);
        self::assertSame('boom', $decoded['error_code'] ?? null);
        self::assertSame('req-json-1', $decoded['request_id'] ?? null);
    }

    public function testAppRenderSafeHtmlErrorPageWritesSafeHtml(): void
    {
        ob_start();
        appRenderSafeHtmlErrorPage(500);
        $output = (string) ob_get_clean();

        self::assertStringContainsString('<!doctype html>', strtolower($output));
        self::assertStringContainsString('Ocorreu um erro inesperado.', $output);
    }

    private function resetRequestId(): void
    {
        $ref = new \ReflectionProperty(RequestContext::class, 'requestId');
        $ref->setValue(null, null);
    }
}

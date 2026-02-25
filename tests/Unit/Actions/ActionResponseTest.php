<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActionResponse;
use PHPUnit\Framework\TestCase;

final class ActionResponseTest extends TestCase
{
    public function testRedirectResponseFactoryAndGetters(): void
    {
        $response = ActionResponse::redirect('../public/dashboard.php', ['success_message' => 'OK'], 303);

        self::assertTrue($response->isRedirect());
        self::assertFalse($response->isJson());
        self::assertFalse($response->isCsv());
        self::assertSame(303, $response->getStatusCode());
        self::assertSame('../public/dashboard.php', $response->getRedirectPath());
        self::assertSame(['success_message' => 'OK'], $response->getFlash());
        self::assertSame([], $response->getPayload());
    }

    public function testJsonResponseFactoryAndGetters(): void
    {
        $payload = ['success' => true, 'data' => ['x' => 1]];
        $response = ActionResponse::json($payload, 201);

        self::assertFalse($response->isRedirect());
        self::assertTrue($response->isJson());
        self::assertFalse($response->isCsv());
        self::assertSame(201, $response->getStatusCode());
        self::assertNull($response->getRedirectPath());
        self::assertSame([], $response->getFlash());
        self::assertSame($payload, $response->getPayload());
    }

    public function testCsvResponseFactoryAndGetters(): void
    {
        $response = ActionResponse::csv('report.csv', "a,b\n1,2\n", 200);

        self::assertFalse($response->isRedirect());
        self::assertFalse($response->isJson());
        self::assertTrue($response->isCsv());
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('report.csv', $response->getPayload()['filename'] ?? null);
        self::assertSame("a,b\n1,2\n", $response->getPayload()['content'] ?? null);
    }
}

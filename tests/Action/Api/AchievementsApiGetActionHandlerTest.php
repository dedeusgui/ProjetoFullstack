<?php

declare(strict_types=1);

namespace Tests\Action\Api;

use App\Actions\Api\AchievementsApiGetActionHandler;
use Tests\Support\ActionTestCase;

final class AchievementsApiGetActionHandlerTest extends ActionTestCase
{
    public function testUnauthorizedRequestReturnsJson401(): void
    {
        $handler = new AchievementsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], []);

        $response = $handler->handle($this->conn(), []);

        self::assertTrue($response->isJson());
        self::assertSame(401, $response->getStatusCode());

        $payload = $response->getPayload();
        self::assertFalse($payload['success'] ?? true);
        self::assertSame('unauthorized', $payload['error_code'] ?? null);
        self::assertIsString($payload['message'] ?? null);
        self::assertStringContainsString('autenticado', (string) ($payload['message'] ?? ''));
        self::assertArrayHasKey('request_id', $payload);
        self::assertIsString($payload['request_id']);
        self::assertNotSame('', $payload['request_id']);
    }

    public function testAuthenticatedRequestReturnsAchievementsPayload(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);
        $handler = new AchievementsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), ['user_id' => $userId]);

        self::assertTrue($response->isJson());
        self::assertSame(200, $response->getStatusCode());

        $payload = $response->getPayload();
        self::assertTrue($payload['success'] ?? false);
        self::assertArrayHasKey('data', $payload);
        self::assertArrayHasKey('hero', $payload['data']);
        self::assertArrayHasKey('achievements', $payload['data']);
        self::assertArrayHasKey('highlights', $payload['data']);
        self::assertArrayHasKey('stats', $payload['data']);
    }
}

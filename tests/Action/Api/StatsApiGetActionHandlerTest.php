<?php

declare(strict_types=1);

namespace Tests\Action\Api;

use App\Actions\Api\StatsApiGetActionHandler;
use Tests\Support\ActionTestCase;

final class StatsApiGetActionHandlerTest extends ActionTestCase
{
    public function testUnauthorizedRequestReturnsJson401(): void
    {
        $handler = new StatsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], []);

        $response = $handler->handle($this->conn(), [], $_SERVER, $_SESSION);

        self::assertTrue($response->isJson());
        self::assertSame(401, $response->getStatusCode());

        $payload = $response->getPayload();
        self::assertFalse($payload['success'] ?? true);
        self::assertSame('unauthorized', $payload['error_code'] ?? null);
        self::assertSame('Usuário não autenticado.', $payload['message'] ?? null);
        self::assertIsString($payload['request_id'] ?? null);
        self::assertNotSame('', $payload['request_id'] ?? '');
    }

    public function testDefaultsToDashboardViewWhenMissing(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new StatsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), [], $_SERVER, $_SESSION);

        self::assertTrue($response->isJson());
        self::assertSame(200, $response->getStatusCode());
        $payload = $response->getPayload();

        self::assertTrue($payload['success'] ?? false);
        self::assertSame('dashboard', $payload['view'] ?? null);
        self::assertArrayHasKey('generated_at', $payload);
        self::assertNotFalse(strtotime((string) $payload['generated_at']));
        self::assertArrayHasKey('data', $payload);
    }

    public function testInvalidViewFallsBackToDashboard(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new StatsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), ['view' => 'oops'], $_SERVER, $_SESSION);

        self::assertSame('dashboard', $response->getPayload()['view'] ?? null);
    }

    public function testHistoryViewReturnsHistoryPayload(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new StatsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), ['view' => 'history'], $_SERVER, $_SESSION);

        $payload = $response->getPayload();
        self::assertSame('history', $payload['view'] ?? null);
        self::assertTrue($payload['success'] ?? false);
        self::assertArrayHasKey('data', $payload);
        self::assertArrayHasKey('stats', $payload['data']);
        self::assertArrayHasKey('monthly_data', $payload['data']);
        self::assertArrayHasKey('recent_history', $payload['data']);
    }
}


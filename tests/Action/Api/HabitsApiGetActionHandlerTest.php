<?php

declare(strict_types=1);

namespace Tests\Action\Api;

use App\Actions\Api\HabitsApiGetActionHandler;
use Tests\Support\ActionTestCase;

final class HabitsApiGetActionHandlerTest extends ActionTestCase
{
    public function testUnauthorizedRequestReturnsJson401(): void
    {
        $handler = new HabitsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], []);

        $response = $handler->handle($this->conn(), [], $_SERVER, $_SESSION);

        self::assertTrue($response->isJson());
        self::assertSame(401, $response->getStatusCode());

        $payload = $response->getPayload();
        self::assertFalse($payload['success'] ?? true);
        self::assertSame('unauthorized', $payload['error_code'] ?? null);
        self::assertArrayHasKey('request_id', $payload);
    }

    public function testDefaultsToAllScopeWhenMissing(): void
    {
        $userId = $this->fixtures->createUser();
        $this->fixtures->createHabit($userId);
        $handler = new HabitsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), [], $_SERVER, $_SESSION);

        $payload = $response->getPayload();
        self::assertTrue($payload['success'] ?? false);
        self::assertSame('all', $payload['scope'] ?? null);
        self::assertArrayHasKey('generated_at', $payload);
        self::assertArrayHasKey('data', $payload);
        self::assertArrayHasKey('count', $payload['data']);
        self::assertArrayHasKey('habits', $payload['data']);
    }

    public function testInvalidScopeFallsBackToAll(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new HabitsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), ['scope' => 'bad'], $_SERVER, $_SESSION);

        self::assertSame('all', $response->getPayload()['scope'] ?? null);
    }

    public function testTodayScopeReturnsCountAndHabits(): void
    {
        $userId = $this->fixtures->createUser();
        $this->fixtures->createHabit($userId, ['frequency' => 'daily']);
        $handler = new HabitsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), ['scope' => 'today'], $_SERVER, $_SESSION);

        $payload = $response->getPayload();
        self::assertSame('today', $payload['scope'] ?? null);
        self::assertArrayHasKey('count', $payload['data']);
        self::assertArrayHasKey('habits', $payload['data']);
    }

    public function testPageScopeReturnsPageSpecificKeys(): void
    {
        $userId = $this->fixtures->createUser();
        $this->fixtures->createHabit($userId, ['frequency' => 'daily']);
        $handler = new HabitsApiGetActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), ['scope' => 'page'], $_SERVER, $_SESSION);

        $payload = $response->getPayload();
        self::assertSame('page', $payload['scope'] ?? null);
        self::assertArrayHasKey('today_date', $payload['data']);
        self::assertArrayHasKey('stats', $payload['data']);
        self::assertArrayHasKey('habits', $payload['data']);
        self::assertArrayHasKey('archived_habits', $payload['data']);
        self::assertArrayHasKey('habits_by_week_day', $payload['data']);
        self::assertArrayHasKey('categories', $payload['data']);
    }
}

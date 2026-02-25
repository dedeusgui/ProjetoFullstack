<?php

declare(strict_types=1);

namespace Tests\Action\Auth;

use App\Actions\Auth\LogoutActionHandler;
use PHPUnit\Framework\TestCase;

final class LogoutActionHandlerTest extends TestCase
{
    public function testClearsSessionAndRedirectsToLogin(): void
    {
        $handler = new LogoutActionHandler();
        $session = [
            'user_id' => 10,
            'user_name' => 'Logout User',
            'user_email' => 'logout@example.com',
            'csrf_token' => 'token',
            'custom' => 'value',
        ];

        $response = $handler->handle($session);

        self::assertSame([], $session);
        self::assertTrue($response->isRedirect());
        self::assertSame('../public/login.php', $response->getRedirectPath());
    }

    public function testWorksWithEmptySession(): void
    {
        $handler = new LogoutActionHandler();
        $session = [];

        $response = $handler->handle($session);

        self::assertSame([], $session);
        self::assertTrue($response->isRedirect());
        self::assertSame('../public/login.php', $response->getRedirectPath());
    }
}

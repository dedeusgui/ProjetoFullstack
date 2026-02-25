<?php

declare(strict_types=1);

namespace Tests\Action\Profile;

use App\Actions\Profile\UpdateProfileActionHandler;
use Tests\Support\ActionTestCase;

final class UpdateProfileActionHandlerTest extends ActionTestCase
{
    public function testRedirectsToLoginWhenUnauthenticated(): void
    {
        $handler = new UpdateProfileActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'POST'], [], []);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
    }

    public function testInvalidCsrfRedirectsToDashboardWithError(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new UpdateProfileActionHandler();
        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            $this->validPayload() + ['csrf_token' => 'bad', 'return_to' => 'history.php'],
            ['user_id' => $userId, 'csrf_token' => 'good']
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/dashboard.php');
        self::assertArrayHasKey('error_message', $response->getFlash());
    }

    public function testSuccessfulUpdateUsesAllowedReturnPathAndUpdatesSessionEmail(): void
    {
        $userId = $this->fixtures->createUser(['email' => 'old@example.com']);
        $handler = new UpdateProfileActionHandler();
        $token = 'csrf-profile-update';
        $payload = array_merge($this->validPayload(), [
            'email' => 'new@example.com',
            'return_to' => 'history.php',
            'csrf_token' => $token,
        ]);

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            $payload,
            ['user_id' => $userId, 'user_email' => 'old@example.com', 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/history.php');
        self::assertArrayHasKey('success_message', $response->getFlash());
        self::assertSame('new@example.com', $_SESSION['user_email'] ?? null);
    }

    public function testInvalidReturnPathFallsBackToDashboard(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new UpdateProfileActionHandler();
        $token = 'csrf-profile-fallback';
        $payload = array_merge($this->validPayload(), [
            'return_to' => 'admin.php',
            'csrf_token' => $token,
        ]);

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            $payload,
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/dashboard.php');
    }

    private function validPayload(): array
    {
        return [
            'email' => 'profile@example.com',
            'avatar_url' => '',
            'new_password' => '',
            'confirm_password' => '',
            'current_password' => '',
            'theme' => 'light',
            'primary_color' => '#4A74FF',
            'accent_color' => '#59D186',
            'text_scale' => '1.0',
        ];
    }
}

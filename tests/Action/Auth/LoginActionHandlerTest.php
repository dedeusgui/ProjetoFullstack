<?php

declare(strict_types=1);

namespace Tests\Action\Auth;

use App\Actions\Auth\LoginActionHandler;
use Tests\Support\ActionTestCase;

final class LoginActionHandlerTest extends ActionTestCase
{
    public function testNonPostRequestRedirectsToLogin(): void
    {
        $handler = new LoginActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], []);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
        self::assertSame([], $response->getFlash());
    }

    public function testInvalidCsrfRedirectsWithErrorFlash(): void
    {
        $handler = new LoginActionHandler();
        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['email' => 'user@example.com', 'password' => 'secret123', 'csrf_token' => 'bad'],
            ['csrf_token' => 'good']
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
        self::assertSame(
            'Sessao invalida. Atualize a pagina e tente novamente.',
            $response->getFlash()['error_message'] ?? null
        );
    }

    public function testMissingFieldsRedirectsWithErrorFlash(): void
    {
        $handler = new LoginActionHandler();
        $token = 'csrf-login-missing';
        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['email' => '', 'password' => '', 'csrf_token' => $token],
            ['csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
        self::assertSame('Por favor, preencha todos os campos.', $response->getFlash()['error_message'] ?? null);
    }

    public function testRateLimitedLoginRedirectsWithErrorFlash(): void
    {
        $handler = new LoginActionHandler();
        $token = 'csrf-login-rate';
        $ip = '127.0.0.10';
        $attemptKey = $this->attemptKey($ip);

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => $ip],
            ['email' => 'user@example.com', 'password' => 'secret123', 'csrf_token' => $token],
            [
                'csrf_token' => $token,
                $attemptKey => ['count' => 5, 'first_attempt_at' => time()],
            ]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
        self::assertSame(
            'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.',
            $response->getFlash()['error_message'] ?? null
        );
        self::assertArrayHasKey($attemptKey, $_SESSION);
        self::assertSame(5, (int) ($_SESSION[$attemptKey]['count'] ?? 0));
    }

    public function testInvalidCredentialsRegistersFailureState(): void
    {
        $this->fixtures->createUser([
            'email' => 'login@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);
        $handler = new LoginActionHandler();
        $token = 'csrf-login-invalid';
        $ip = '127.0.0.11';
        $attemptKey = $this->attemptKey($ip);

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => $ip],
            ['email' => 'login@example.com', 'password' => 'wrong', 'csrf_token' => $token],
            ['csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
        self::assertSame('Email ou senha incorretos.', $response->getFlash()['error_message'] ?? null);
        self::assertArrayHasKey($attemptKey, $_SESSION);
        self::assertSame(1, (int) ($_SESSION[$attemptKey]['count'] ?? 0));
        self::assertIsInt($_SESSION[$attemptKey]['first_attempt_at'] ?? null);
        self::assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testSuccessfulLoginClearsFailureStateSetsSessionAndUpdatesLastLogin(): void
    {
        $userId = $this->fixtures->createUser([
            'name' => 'Login User',
            'email' => 'login.ok@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);
        $handler = new LoginActionHandler();
        $token = 'csrf-login-success';
        $ip = '127.0.0.12';
        $attemptKey = $this->attemptKey($ip);

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => $ip],
            ['email' => '  LOGIN.OK@EXAMPLE.COM ', 'password' => 'secret123', 'csrf_token' => $token],
            [
                'csrf_token' => $token,
                $attemptKey => ['count' => 3, 'first_attempt_at' => time()],
            ]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/dashboard.php');
        self::assertSame([], $response->getFlash());
        self::assertSame($userId, $_SESSION['user_id'] ?? null);
        self::assertSame('Login User', $_SESSION['user_name'] ?? null);
        self::assertSame('login.ok@example.com', $_SESSION['user_email'] ?? null);
        self::assertIsInt($_SESSION['logged_in_at'] ?? null);
        self::assertArrayNotHasKey($attemptKey, $_SESSION);

        $row = $this->db()->fetchOne('SELECT last_login FROM users WHERE id = ' . (int) $userId);
        self::assertNotNull($row['last_login'] ?? null);
    }

    private function attemptKey(string $ip): string
    {
        return 'auth_attempts_' . hash('sha256', $ip);
    }
}

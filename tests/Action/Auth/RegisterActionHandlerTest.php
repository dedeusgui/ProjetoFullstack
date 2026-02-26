<?php

declare(strict_types=1);

namespace Tests\Action\Auth;

use App\Actions\Auth\RegisterActionHandler;
use Tests\Support\ActionTestCase;

final class RegisterActionHandlerTest extends ActionTestCase
{
    public function testNonPostRequestRedirectsToRegister(): void
    {
        $handler = new RegisterActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], []);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/register.php');
        self::assertSame([], $response->getFlash());
    }

    public function testInvalidCsrfRedirectsWithErrorFlash(): void
    {
        $handler = new RegisterActionHandler();
        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            $this->validPayload() + ['csrf_token' => 'bad'],
            ['csrf_token' => 'good']
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/register.php');
        self::assertSame(
            'Sessão inválida. Atualize a página e tente novamente.',
            $response->getFlash()['error_message'] ?? null
        );
    }

    public function testMissingFieldsRedirectsWithErrorFlash(): void
    {
        $handler = new RegisterActionHandler();
        $token = 'csrf-register-missing';
        $payload = $this->validPayload();
        $payload['name'] = '';

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload + ['csrf_token' => $token], ['csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/register.php');
        self::assertSame('Por favor, preencha todos os campos.', $response->getFlash()['error_message'] ?? null);
    }

    public function testInvalidEmailRedirectsWithErrorFlash(): void
    {
        $handler = new RegisterActionHandler();
        $token = 'csrf-register-email';
        $payload = $this->validPayload();
        $payload['email'] = 'invalid-email';

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload + ['csrf_token' => $token], ['csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/register.php');
        self::assertSame('E-mail inválido.', $response->getFlash()['error_message'] ?? null);
    }

    public function testShortPasswordRedirectsWithErrorFlash(): void
    {
        $handler = new RegisterActionHandler();
        $token = 'csrf-register-short';
        $payload = $this->validPayload();
        $payload['password'] = '12345';
        $payload['confirm_password'] = '12345';

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload + ['csrf_token' => $token], ['csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/register.php');
        self::assertSame(
            'A senha deve ter no mínimo 6 caracteres.',
            $response->getFlash()['error_message'] ?? null
        );
    }

    public function testPasswordMismatchRedirectsWithErrorFlash(): void
    {
        $handler = new RegisterActionHandler();
        $token = 'csrf-register-mismatch';
        $payload = $this->validPayload();
        $payload['confirm_password'] = 'different123';

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload + ['csrf_token' => $token], ['csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/register.php');
        self::assertSame('As senhas não conferem.', $response->getFlash()['error_message'] ?? null);
    }

    public function testDuplicateEmailRedirectsWithErrorFlash(): void
    {
        $this->fixtures->createUser([
            'email' => 'duplicate.register@example.com',
        ]);
        $handler = new RegisterActionHandler();
        $token = 'csrf-register-dup';
        $payload = $this->validPayload();
        $payload['email'] = 'DUPLICATE.REGISTER@EXAMPLE.COM';

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload + ['csrf_token' => $token], ['csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/register.php');
        self::assertSame('Este e-mail já está cadastrado.', $response->getFlash()['error_message'] ?? null);
    }

    public function testSuccessfulRegistrationCreatesUserAndSignsIn(): void
    {
        $handler = new RegisterActionHandler();
        $token = 'csrf-register-success';
        $payload = $this->validPayload();
        $payload['name'] = 'New Register User';
        $payload['email'] = '  NEW.REGISTER@EXAMPLE.COM ';
        $payload['password'] = 'secret123';
        $payload['confirm_password'] = 'secret123';

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload + ['csrf_token' => $token], ['csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/dashboard.php');
        self::assertSame([], $response->getFlash());
        self::assertArrayHasKey('user_id', $_SESSION);
        self::assertSame('New Register User', $_SESSION['user_name'] ?? null);
        self::assertSame('new.register@example.com', $_SESSION['user_email'] ?? null);
        self::assertIsInt($_SESSION['logged_in_at'] ?? null);

        $row = $this->db()->fetchOne("SELECT id, name, email, password FROM users WHERE email = 'new.register@example.com' LIMIT 1");
        self::assertNotNull($row);
        self::assertSame((string) ($_SESSION['user_id'] ?? ''), (string) ($row['id'] ?? ''));
        self::assertSame('New Register User', $row['name'] ?? null);
        self::assertNotSame('secret123', $row['password'] ?? null);
        self::assertTrue(password_verify('secret123', (string) ($row['password'] ?? '')));
    }

    private function validPayload(): array
    {
        return [
            'name' => 'Register User',
            'email' => 'register@example.com',
            'password' => 'secret123',
            'confirm_password' => 'secret123',
        ];
    }
}


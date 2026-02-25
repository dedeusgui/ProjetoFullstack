<?php

namespace App\Actions\Auth;

use App\Actions\ActionResponse;
use App\Auth\AuthService;

final class RegisterActionHandler
{
    public function handle(\mysqli $conn, array $post, array $server, array &$session): ActionResponse
    {
        if (($server['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return ActionResponse::redirect('../public/register.php');
        }

        if (!$this->hasValidCsrfToken($post, $session)) {
            return ActionResponse::redirect('../public/register.php', [
                'error_message' => 'Sessao invalida. Atualize a pagina e tente novamente.',
            ]);
        }

        $name = trim((string) ($post['name'] ?? ''));
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');
        $confirmPassword = (string) ($post['confirm_password'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
            return ActionResponse::redirect('../public/register.php', [
                'error_message' => 'Por favor, preencha todos os campos.',
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ActionResponse::redirect('../public/register.php', [
                'error_message' => 'Email invalido.',
            ]);
        }

        if (strlen($password) < 6) {
            return ActionResponse::redirect('../public/register.php', [
                'error_message' => 'A senha deve ter no minimo 6 caracteres.',
            ]);
        }

        if ($password !== $confirmPassword) {
            return ActionResponse::redirect('../public/register.php', [
                'error_message' => 'As senhas nao conferem.',
            ]);
        }

        $authService = new AuthService($conn);
        if ($authService->emailExists($email)) {
            return ActionResponse::redirect('../public/register.php', [
                'error_message' => 'Este email ja esta cadastrado.',
            ]);
        }

        $user = $authService->register($name, $email, $password);
        if ($user === null) {
            return ActionResponse::redirect('../public/register.php', [
                'error_message' => 'Erro ao criar conta. Tente novamente.',
            ]);
        }

        $this->signIn($session, (int) $user['id'], (string) $user['name'], (string) $user['email']);

        return ActionResponse::redirect('../public/dashboard.php');
    }

    private function hasValidCsrfToken(array $post, array &$session): bool
    {
        if (empty($session['csrf_token'])) {
            $session['csrf_token'] = bin2hex(random_bytes(32));
        }

        $submittedToken = $post['csrf_token'] ?? '';
        if (!is_string($submittedToken)) {
            return false;
        }

        return hash_equals((string) $session['csrf_token'], $submittedToken);
    }

    private function signIn(array &$session, int $userId, string $userName, string $userEmail): void
    {
        $session['user_id'] = $userId;
        $session['user_name'] = $userName;
        $session['user_email'] = $userEmail;
        $session['logged_in_at'] = time();
    }
}

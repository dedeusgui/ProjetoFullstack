<?php

namespace App\Actions\Auth;

use App\Actions\ActionResponse;
use App\Auth\AuthService;

final class LoginActionHandler
{
    public function handle(\mysqli $conn, array $post, array $server, array &$session): ActionResponse
    {
        if (($server['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return ActionResponse::redirect('../public/login.php');
        }

        if (!$this->hasValidCsrfToken($post, $session)) {
            return ActionResponse::redirect('../public/login.php', [
                'error_message' => 'Sessão inválida. Atualize a página e tente novamente.',
            ]);
        }

        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');

        if ($email === '' || $password === '') {
            return ActionResponse::redirect('../public/login.php', [
                'error_message' => 'Por favor, preencha todos os campos.',
            ]);
        }

        if ($this->isRateLimited($server, $session)) {
            return ActionResponse::redirect('../public/login.php', [
                'error_message' => 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.',
            ]);
        }

        $authService = new AuthService($conn);
        $user = $authService->authenticate($email, $password);

        if ($user === null) {
            $this->registerFailure($server, $session);

            return ActionResponse::redirect('../public/login.php', [
                'error_message' => 'E-mail ou senha incorretos.',
            ]);
        }

        $this->clearFailures($server, $session);
        $this->signIn($session, (int) $user['id'], (string) $user['name'], (string) $user['email']);
        $authService->updateLastLogin((int) $user['id']);

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

    private function isRateLimited(array $server, array $session, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $key = $this->attemptKey($server);
        $state = $session[$key] ?? ['count' => 0, 'first_attempt_at' => time()];

        if (!is_array($state) || !isset($state['count'], $state['first_attempt_at'])) {
            return false;
        }

        if ((time() - (int) $state['first_attempt_at']) > $windowSeconds) {
            return false;
        }

        return (int) $state['count'] >= $maxAttempts;
    }

    private function registerFailure(array $server, array &$session, int $windowSeconds = 900): void
    {
        $key = $this->attemptKey($server);
        $state = $session[$key] ?? ['count' => 0, 'first_attempt_at' => time()];

        if ((time() - (int) ($state['first_attempt_at'] ?? time())) > $windowSeconds) {
            $state = ['count' => 0, 'first_attempt_at' => time()];
        }

        $state['count'] = (int) ($state['count'] ?? 0) + 1;
        $session[$key] = $state;
    }

    private function clearFailures(array $server, array &$session): void
    {
        unset($session[$this->attemptKey($server)]);
    }

    private function attemptKey(array $server): string
    {
        $ip = (string) ($server['REMOTE_ADDR'] ?? 'unknown');

        return 'auth_attempts_' . hash('sha256', $ip);
    }
}


<?php

namespace App\Actions\Habits;

use App\Actions\ActionResponse;
use App\Habits\HabitCommandService;

final class HabitCreateActionHandler
{
    public function handle(\mysqli $conn, array $post, array $server, array &$session): ActionResponse
    {
        if (!$this->isLoggedIn($session)) {
            return ActionResponse::redirect('../public/login.php');
        }

        if (($server['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return ActionResponse::redirect('../public/habits.php');
        }

        if (!$this->hasValidCsrfToken($post, $session)) {
            return ActionResponse::redirect('../public/habits.php', [
                'error_message' => 'Sessão inválida. Atualize a página e tente novamente.',
            ]);
        }

        $userId = (int) ($session['user_id'] ?? 0);
        $service = new HabitCommandService($conn);
        $result = $service->create($userId, $post);

        return ActionResponse::redirect('../public/habits.php', [
            $result['success'] ? 'success_message' : 'error_message' => (string) ($result['message'] ?? ''),
        ]);
    }

    private function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']) && !empty($session['user_id']);
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
}

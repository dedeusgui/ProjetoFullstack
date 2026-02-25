<?php

namespace App\Actions\Habits;

use App\Actions\ActionResponse;
use App\Habits\HabitCompletionService;
use App\Support\UserLocalDateResolver;

final class HabitToggleCompletionActionHandler
{
    public function handle(\mysqli $conn, array $post, array $server, array &$session): ActionResponse
    {
        $redirectPath = HabitRefererRedirectResolver::resolve($server);

        try {
            if (!$this->isLoggedIn($session)) {
                return ActionResponse::redirect('../public/login.php');
            }

            if (($server['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                return ActionResponse::redirect('../public/habits.php');
            }

            if (!$this->hasValidCsrfToken($post, $session)) {
                return ActionResponse::redirect('../public/habits.php', [
                    'error_message' => 'Sessao invalida. Atualize a pagina e tente novamente.',
                ]);
            }

            $userId = (int) ($session['user_id'] ?? 0);
            $habitId = (int) ($post['habit_id'] ?? $post['id'] ?? 0);

            if ($habitId <= 0) {
                return ActionResponse::redirect($redirectPath, [
                    'error_message' => 'Habito invalido.',
                ]);
            }

            $dateResolver = new UserLocalDateResolver($conn);
            $completionDate = $post['completion_date'] ?? $dateResolver->getTodayDateForUser($userId);
            $notes = isset($post['notes']) ? (string) $post['notes'] : null;
            $mood = isset($post['mood']) ? (string) $post['mood'] : null;
            $valueAchieved = isset($post['value_achieved']) ? (float) $post['value_achieved'] : null;

            $service = new HabitCompletionService($conn);
            $result = $service->toggleCompletion($habitId, $userId, (string) $completionDate, $valueAchieved, $notes, $mood);

            return ActionResponse::redirect($redirectPath, [
                $result['success'] ? 'success_message' : 'error_message' => (string) ($result['message'] ?? ''),
            ]);
        } catch (\Throwable $exception) {
            if (\function_exists('appLogThrowable')) {
                \appLogThrowable($exception, ['action' => 'habit_toggle_completion_action']);
            }

            return ActionResponse::redirect($redirectPath, [
                'error_message' => 'Ocorreu um erro inesperado. Tente novamente.',
            ]);
        }
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

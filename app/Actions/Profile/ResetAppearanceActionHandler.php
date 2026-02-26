<?php

namespace App\Actions\Profile;

use App\Actions\ActionResponse;
use App\Profile\ProfileService;

final class ResetAppearanceActionHandler
{
    public function handle(\mysqli $conn, array $post, array $server, array &$session): ActionResponse
    {
        if (!$this->isLoggedIn($session)) {
            return ActionResponse::redirect('../public/login.php');
        }

        $redirectPath = $this->resolveReturnPath($post);

        if (($server['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return ActionResponse::redirect('../public/dashboard.php');
        }

        if (!$this->hasValidCsrfToken($post, $session)) {
            return ActionResponse::redirect('../public/dashboard.php', [
                'error_message' => 'Sessão inválida. Atualize a página e tente novamente.',
            ]);
        }

        $userId = (int) ($session['user_id'] ?? 0);
        $service = new ProfileService($conn);
        $result = $service->resetAppearance($userId);

        return ActionResponse::redirect($redirectPath, [
            !empty($result['success']) ? 'success_message' : 'error_message' => (string) ($result['message'] ?? ''),
        ]);
    }

    private function resolveReturnPath(array $post): string
    {
        $allowedPages = ['dashboard.php', 'habits.php', 'history.php'];
        $returnTo = trim((string) ($post['return_to'] ?? 'dashboard.php'));

        if (!in_array($returnTo, $allowedPages, true)) {
            $returnTo = 'dashboard.php';
        }

        return '../public/' . $returnTo;
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


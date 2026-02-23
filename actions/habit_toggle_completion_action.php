<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Habits\HabitCompletionService;
use App\Support\UserLocalDateResolver;

function redirectBack(): void
{
    $fallback = '../public/habits.php';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    if (!is_string($referer) || $referer === '') {
        actionRedirect($fallback);
    }

    $parts = parse_url($referer);
    if ($parts === false) {
        actionRedirect($fallback);
    }

    $path = (string) ($parts['path'] ?? '');
    if ($path === '' || $path[0] !== '/' || str_contains($path, "\r") || str_contains($path, "\n")) {
        actionRedirect($fallback);
    }

    $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $refererHost = strtolower((string) ($parts['host'] ?? ''));
    if ($refererHost !== '' && $requestHost !== '' && !hash_equals($requestHost, $refererHost)) {
        actionRedirect($fallback);
    }

    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    actionRedirect($path . $query);
}

try {
    actionRequireLoggedIn();
    actionRequirePost('habits.php');
    actionRequireCsrf('habits.php');

    $userId = (int) getAuthenticatedUserId();
    $habitId = (int) ($_POST['habit_id'] ?? $_POST['id'] ?? 0);
    $userLocalDateResolver = new UserLocalDateResolver($conn);
    $completionDate = $_POST['completion_date'] ?? $userLocalDateResolver->getTodayDateForUser($userId);

    if ($habitId <= 0) {
        $_SESSION['error_message'] = 'HÃ¡bito invÃ¡lido.';
        redirectBack();
    }

    $notes = $_POST['notes'] ?? null;
    $mood = $_POST['mood'] ?? null;
    $valueAchieved = isset($_POST['value_achieved']) ? (float) $_POST['value_achieved'] : null;

    $habitCompletionService = new HabitCompletionService($conn);
    $result = $habitCompletionService->toggleCompletion($habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);

    $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
    redirectBack();
} catch (\Throwable $exception) {
    appLogThrowable($exception, ['action' => 'habit_toggle_completion_action']);
    $_SESSION['error_message'] = 'Ocorreu um erro inesperado. Tente novamente.';
    redirectBack();
}

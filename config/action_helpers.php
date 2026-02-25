<?php

use App\Actions\ActionResponse;

function actionRedirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function actionCurrentRequestId(): string
{
    return appRequestId();
}

function actionIsJsonRequest(): bool
{
    return appIsJsonRequest();
}

function actionRedirectPublic(string $page): void
{
    actionRedirect('../public/' . ltrim($page, '/'));
}

function actionRequireLoggedIn(): void
{
    if (!isUserLoggedIn()) {
        actionRedirectPublic('login.php');
    }
}

function actionRequirePost(string $fallbackPublicPage): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        actionRedirectPublic($fallbackPublicPage);
    }
}

function actionResolveReturnPath(array $allowedPages, string $defaultPage = 'dashboard.php'): string
{
    $returnTo = trim($_POST['return_to'] ?? $defaultPage);
    if (!in_array($returnTo, $allowedPages, true)) {
        $returnTo = $defaultPage;
    }

    return '../public/' . $returnTo;
}

function actionFlashAndRedirect(string $sessionKey, string $message, string $path): void
{
    $_SESSION[$sessionKey] = $message;
    actionRedirect($path);
}

function actionApplyResponse(ActionResponse $response): void
{
    if ($response->isRedirect()) {
        foreach ($response->getFlash() as $key => $message) {
            $_SESSION[(string) $key] = (string) $message;
        }

        actionRedirect($response->getRedirectPath() ?? '../public/dashboard.php');
    }

    if ($response->isJson()) {
        actionJsonResponse($response->getPayload(), $response->getStatusCode());
    }

    throw new RuntimeException('Tipo de resposta de ação não suportado.');
}

function actionJsonResponse(array $payload, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function actionJsonError(string $message, int $statusCode = 500, string $errorCode = 'internal_error', array $extra = []): void
{
    $payload = array_merge([
        'success' => false,
        'message' => $message,
        'error_code' => $errorCode,
        'request_id' => actionCurrentRequestId(),
    ], $extra);

    actionJsonResponse($payload, $statusCode);
}

function actionHandleUnexpectedThrowable(\Throwable $exception, string $fallbackPublicPage = 'dashboard.php'): void
{
    appLogThrowable($exception);

    if (actionIsJsonRequest()) {
        actionJsonError('Ocorreu um erro interno. Tente novamente.', 500, 'internal_error');
    }

    actionFlashAndRedirect('error_message', 'Ocorreu um erro inesperado. Tente novamente.', '../public/' . ltrim($fallbackPublicPage, '/'));
}

function actionRunWithErrorHandling(callable $callback, string $fallbackPublicPage = 'dashboard.php'): void
{
    try {
        $callback();
    } catch (\Throwable $exception) {
        actionHandleUnexpectedThrowable($exception, $fallbackPublicPage);
    }
}

function actionRunApi(callable $callback): void
{
    try {
        $callback();
    } catch (\Throwable $exception) {
        appLogThrowable($exception);
        actionJsonError('Ocorreu um erro interno. Tente novamente.', 500, 'internal_error');
    }
}


function ensureCsrfToken(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function getCsrfToken(): string
{
    ensureCsrfToken();
    return $_SESSION['csrf_token'];
}

function actionRequireCsrf(string $fallbackPublicPage): void
{
    ensureCsrfToken();

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        actionFlashAndRedirect('error_message', 'Sessão inválida. Atualize a página e tente novamente.', '../public/' . ltrim($fallbackPublicPage, '/'));
    }
}


function authAttemptKey(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return 'auth_attempts_' . hash('sha256', $ip);
}

function authIsRateLimited(int $maxAttempts = 5, int $windowSeconds = 900): bool
{
    $key = authAttemptKey();
    $state = $_SESSION[$key] ?? ['count' => 0, 'first_attempt_at' => time()];

    if (!is_array($state) || !isset($state['count'], $state['first_attempt_at'])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt_at' => time()];
        return false;
    }

    if ((time() - (int) $state['first_attempt_at']) > $windowSeconds) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt_at' => time()];
        return false;
    }

    return (int) $state['count'] >= $maxAttempts;
}

function authRegisterFailure(int $windowSeconds = 900): void
{
    $key = authAttemptKey();
    $state = $_SESSION[$key] ?? ['count' => 0, 'first_attempt_at' => time()];

    if ((time() - (int) ($state['first_attempt_at'] ?? time())) > $windowSeconds) {
        $state = ['count' => 0, 'first_attempt_at' => time()];
    }

    $state['count'] = (int) ($state['count'] ?? 0) + 1;
    $_SESSION[$key] = $state;
}

function authClearFailures(): void
{
    $key = authAttemptKey();
    unset($_SESSION[$key]);
}

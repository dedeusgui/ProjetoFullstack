<?php

function actionRedirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function actionRedirectPublic(string $page): void
{
    actionRedirect('../public/' . ltrim($page, '/'));
}

function actionRequireLoggedIn(): void
{
    if (!isLoggedIn()) {
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

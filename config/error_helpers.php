<?php

use App\Support\AppLogger;
use App\Support\RequestContext;

function initAppErrorHandling(): void
{
    static $initialized = false;
    if ($initialized) {
        RequestContext::ensureRequestId();
        return;
    }

    $initialized = true;

    error_reporting(E_ALL);
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');

    RequestContext::ensureRequestId();

    set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler(static function (\Throwable $exception): void {
        appHandleUnhandledThrowable($exception);
    });

    register_shutdown_function(static function (): void {
        $error = error_get_last();
        if (!is_array($error)) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int) ($error['type'] ?? 0), $fatalTypes, true)) {
            return;
        }

        appHandleFatalError($error);
    });
}

function appRequestId(): string
{
    return RequestContext::getRequestId();
}

function appIsJsonRequest(): bool
{
    if (PHP_SAPI === 'cli') {
        return false;
    }

    $scriptName = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_contains($scriptName, '/actions/api_') || str_contains($scriptName, '\\actions\\api_')) {
        return true;
    }

    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    if (str_contains($accept, 'application/json')) {
        return true;
    }

    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    return $requestedWith === 'xmlhttprequest';
}

function appBuildLogContext(array $extra = []): array
{
    $context = [
        'request_id' => appRequestId(),
        'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
        'path' => (string) ($_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? '')),
        'script' => (string) ($_SERVER['SCRIPT_NAME'] ?? ''),
    ];

    if (function_exists('isUserLoggedIn') && function_exists('getAuthenticatedUserId') && isUserLoggedIn()) {
        $context['user_id'] = getAuthenticatedUserId();
    }

    foreach ($extra as $key => $value) {
        $context[$key] = $value;
    }

    return $context;
}

function appLogThrowable(\Throwable $exception, array $context = []): void
{
    $context = appBuildLogContext($context + [
        'exception_class' => get_class($exception),
        'exception_message' => $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);

    AppLogger::error('Unhandled exception', $context);
}

function appLogMessage(string $message, array $context = []): void
{
    AppLogger::error($message, appBuildLogContext($context));
}

function appStatusCodeForThrowable(\Throwable $exception): int
{
    if (method_exists($exception, 'getStatusCode')) {
        $statusCode = (int) $exception->getStatusCode();
        if ($statusCode >= 400 && $statusCode <= 599) {
            return $statusCode;
        }
    }

    return 500;
}

function appSendJsonErrorResponse(string $message, int $statusCode = 500, string $errorCode = 'internal_error'): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'success' => false,
        'message' => $message,
        'error_code' => $errorCode,
        'request_id' => appRequestId(),
    ], JSON_UNESCAPED_UNICODE);
}

function appRenderSafeHtmlErrorPage(int $statusCode = 500): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
    }

    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Erro</title></head><body>';
    echo '<h1>Ocorreu um erro inesperado.</h1><p>Tente novamente em instantes.</p>';
    echo '</body></html>';
}

function appHandleUnhandledThrowable(\Throwable $exception): void
{
    static $handling = false;
    if ($handling) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo 'Erro interno.';
        exit;
    }

    $handling = true;

    try {
        appLogThrowable($exception);
        $statusCode = appStatusCodeForThrowable($exception);

        if (appIsJsonRequest()) {
            appSendJsonErrorResponse('Ocorreu um erro interno. Tente novamente.', $statusCode, 'internal_error');
            exit;
        }

        $scriptName = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $isActionRequest = str_contains($scriptName, '/actions/') || str_contains($scriptName, '\\actions\\');

        if ($isActionRequest && session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            $_SESSION['error_message'] = 'Ocorreu um erro inesperado. Tente novamente.';
            $fallback = (function_exists('isUserLoggedIn') && isUserLoggedIn())
                ? '../public/dashboard.php'
                : '../public/login.php';
            header('Location: ' . $fallback);
            exit;
        }

        appRenderSafeHtmlErrorPage($statusCode);
        exit;
    } catch (\Throwable $handlerException) {
        error_log('Error handler failure: ' . $handlerException->getMessage());
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo 'Erro interno.';
        exit;
    }
}

function appHandleFatalError(array $error): void
{
    appLogMessage('Fatal error', [
        'fatal_type' => (int) ($error['type'] ?? 0),
        'fatal_message' => (string) ($error['message'] ?? ''),
        'file' => (string) ($error['file'] ?? ''),
        'line' => (int) ($error['line'] ?? 0),
    ]);

    if (headers_sent()) {
        return;
    }

    if (appIsJsonRequest()) {
        appSendJsonErrorResponse('Ocorreu um erro interno. Tente novamente.', 500, 'internal_error');
        return;
    }

    appRenderSafeHtmlErrorPage(500);
}

<?php

function appIsHttpsRequest(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off' && $https !== '0') {
        return true;
    }

    $port = (string) ($_SERVER['SERVER_PORT'] ?? '');
    if ($port === '443') {
        return true;
    }

    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($forwardedProto !== '') {
        $firstProto = trim(explode(',', $forwardedProto)[0] ?? '');
        if ($firstProto === 'https') {
            return true;
        }
    }

    return false;
}

function appConfigureSessionCookieParams(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    @ini_set('session.use_strict_mode', '1');

    $cookieParams = session_get_cookie_params();
    $secure = appIsHttpsRequest();

    session_set_cookie_params([
        'lifetime' => (int) ($cookieParams['lifetime'] ?? 0),
        'path' => '/',
        'domain' => (string) ($cookieParams['domain'] ?? ''),
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function appEnsureCspNonce(): string
{
    if (isset($GLOBALS['__app_csp_nonce']) && is_string($GLOBALS['__app_csp_nonce']) && $GLOBALS['__app_csp_nonce'] !== '') {
        return $GLOBALS['__app_csp_nonce'];
    }

    try {
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    } catch (\Throwable $exception) {
        $nonce = substr(hash('sha256', uniqid('csp_', true)), 0, 22);
    }

    $GLOBALS['__app_csp_nonce'] = $nonce;
    return $nonce;
}

function appGetCspNonce(): string
{
    return appEnsureCspNonce();
}

function appBuildContentSecurityPolicy(): string
{
    $directives = [
        "default-src 'self'",
        "base-uri 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "form-action 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com",
        "img-src 'self' data: https: http:",
        "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com",
        "connect-src 'self'",
        "frame-src 'none'",
    ];

    return implode('; ', $directives);
}

function appSendSecurityHeaders(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: ' . appBuildContentSecurityPolicy());
}

<?php

/**
 * Shared web application bootstrap.
 *
 * - Ensures an active session
 * - Loads common infrastructure helpers
 * - Optionally loads the database connection
 */
function bootApp(bool $loadDatabase = true): void
{
    static $autoloadLoaded = false;
    if (!$autoloadLoaded) {
        $composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!file_exists($composerAutoload)) {
            throw new RuntimeException(
                'Composer autoload file not found at ' . $composerAutoload .
                '. Run "composer install" (or "composer dump-autoload") in the project root.'
            );
        }
        require_once $composerAutoload;
        $autoloadLoaded = true;
    }

    appSendSecurityHeaders();
    appConfigureSessionCookieParams();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    initAppErrorHandling();

    if ($loadDatabase) {
        global $conn;
        require_once __DIR__ . '/database.php';
    }
}


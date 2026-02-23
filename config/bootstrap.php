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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    static $autoloadLoaded = false;
    if (!$autoloadLoaded) {
        $composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
        } else {
            spl_autoload_register(static function (string $class): void {
                $prefix = 'App\\';
                if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                    return;
                }

                $relative = substr($class, strlen($prefix));
                if ($relative === false) {
                    return;
                }

                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
                $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

                $candidates = [
                    $baseDir . $relativePath,
                    // Compatibilidade com estrutura atual (pastas minÃºsculas)
                    $baseDir . strtolower($relativePath),
                ];

                foreach ($candidates as $candidate) {
                    if (file_exists($candidate)) {
                        require_once $candidate;
                        return;
                    }
                }
            });
        }
        $autoloadLoaded = true;
    }

    require_once __DIR__ . '/auth_helpers.php';
    require_once __DIR__ . '/app_helpers.php';
    require_once __DIR__ . '/error_helpers.php';
    require_once __DIR__ . '/action_helpers.php';
    initAppErrorHandling();

    if ($loadDatabase) {
        global $conn;
        require_once __DIR__ . '/database.php';
    }
}


<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Auth\RegisterActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new RegisterActionHandler();
    $response = $handler->handle($conn, $_POST, $_SERVER, $_SESSION);

    if (
        $response->isRedirect()
        && $response->getRedirectPath() === '../public/dashboard.php'
        && isset($_SESSION['user_id'])
        && session_status() === PHP_SESSION_ACTIVE
    ) {
        session_regenerate_id(true);
    }

    actionApplyResponse($response);
}, 'register.php');

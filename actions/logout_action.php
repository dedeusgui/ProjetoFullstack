<?php

require_once '../config/bootstrap.php';
bootApp(false);

use App\Actions\Auth\LogoutActionHandler;

$handler = new LogoutActionHandler();
$response = $handler->handle($_SESSION);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

actionApplyResponse($response);

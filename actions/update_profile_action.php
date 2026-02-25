<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Profile\UpdateProfileActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new UpdateProfileActionHandler();
    actionApplyResponse($handler->handle($conn, $_POST, $_SERVER, $_SESSION));
}, 'dashboard.php');

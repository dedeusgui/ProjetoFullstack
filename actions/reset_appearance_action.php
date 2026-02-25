<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Profile\ResetAppearanceActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new ResetAppearanceActionHandler();
    actionApplyResponse($handler->handle($conn, $_POST, $_SERVER, $_SESSION));
}, 'dashboard.php');

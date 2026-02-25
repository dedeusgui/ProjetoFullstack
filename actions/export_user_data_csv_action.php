<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Profile\ExportUserDataCsvActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new ExportUserDataCsvActionHandler();
    actionApplyResponse($handler->handle($conn, $_SERVER, $_SESSION));
}, 'dashboard.php');

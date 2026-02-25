<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Habits\HabitArchiveActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new HabitArchiveActionHandler();
    actionApplyResponse($handler->handle($conn, $_POST, $_SERVER, $_SESSION));
}, 'habits.php');

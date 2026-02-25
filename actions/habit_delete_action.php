<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Habits\HabitDeleteActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new HabitDeleteActionHandler();
    actionApplyResponse($handler->handle($conn, $_POST, $_SERVER, $_SESSION));
}, 'habits.php');

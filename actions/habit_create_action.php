<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Habits\HabitCreateActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new HabitCreateActionHandler();
    actionApplyResponse($handler->handle($conn, $_POST, $_SERVER, $_SESSION));
}, 'habits.php');

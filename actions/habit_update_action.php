<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Habits\HabitUpdateActionHandler;

actionRunWithErrorHandling(static function () use ($conn): void {
    $handler = new HabitUpdateActionHandler();
    actionApplyResponse($handler->handle($conn, $_POST, $_SERVER, $_SESSION));
}, 'habits.php');

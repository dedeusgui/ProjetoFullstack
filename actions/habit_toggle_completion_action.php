<?php

require_once '../config/bootstrap.php';
bootApp();

use App\Actions\Habits\HabitToggleCompletionActionHandler;

$handler = new HabitToggleCompletionActionHandler();
actionApplyResponse($handler->handle($conn, $_POST, $_SERVER, $_SESSION));

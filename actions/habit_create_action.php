<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Habits\HabitCommandService;

actionRunWithErrorHandling(static function () use ($conn): void {
    actionRequireLoggedIn();
    actionRequirePost('habits.php');
    actionRequireCsrf('habits.php');

    $userId = (int) getAuthenticatedUserId();
    $habitCommandService = new HabitCommandService($conn);
    $result = $habitCommandService->create($userId, $_POST);

    actionFlashAndRedirect(
        $result['success'] ? 'success_message' : 'error_message',
        $result['message'],
        '../public/habits.php'
    );
}, 'habits.php');

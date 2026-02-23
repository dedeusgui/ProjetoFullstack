<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Habits\HabitCommandService;

actionRunWithErrorHandling(static function () use ($conn): void {
    actionRequireLoggedIn();
    actionRequirePost('habits.php');
    actionRequireCsrf('habits.php');

    $userId = (int) getAuthenticatedUserId();
    $habitId = (int) ($_POST['habit_id'] ?? $_POST['id'] ?? 0);

    $habitCommandService = new HabitCommandService($conn);
    $result = $habitCommandService->update($userId, $habitId, $_POST);

    actionFlashAndRedirect(
        $result['success'] ? 'success_message' : 'error_message',
        $result['message'],
        '../public/habits.php'
    );
}, 'habits.php');

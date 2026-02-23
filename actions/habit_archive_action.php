<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Habits\HabitCommandService;

actionRunWithErrorHandling(static function () use ($conn): void {
    actionRequireLoggedIn();
    actionRequirePost('habits.php');
    actionRequireCsrf('habits.php');

    $userId = (int) getAuthenticatedUserId();
    $habitId = (int) ($_POST['habit_id'] ?? 0);
    $operation = $_POST['operation'] ?? 'archive';

    $habitCommandService = new HabitCommandService($conn);
    $result = $operation === 'restore'
        ? $habitCommandService->restore($userId, $habitId)
        : $habitCommandService->archive($userId, $habitId);

    actionFlashAndRedirect(
        $result['success'] ? 'success_message' : 'error_message',
        $result['message'],
        '../public/habits.php'
    );
}, 'habits.php');

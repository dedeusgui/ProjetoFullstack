<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Habits\HabitCommandService;

actionRequireLoggedIn();
actionRequirePost('habits.php');
actionRequireCsrf('habits.php');

$userId = (int) getAuthenticatedUserId();
$habitId = (int) ($_POST['habit_id'] ?? $_POST['id'] ?? 0);

$habitCommandService = new HabitCommandService($conn);
$result = $habitCommandService->delete($userId, $habitId);

actionFlashAndRedirect(
    $result['success'] ? 'success_message' : 'error_message',
    $result['message'],
    '../public/habits.php'
);

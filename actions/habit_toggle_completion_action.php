<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Habits\HabitCompletionService;

function redirectBack(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '../public/habits.php';
    actionRedirect($referer);
}

actionRequireLoggedIn();
actionRequirePost('habits.php');
actionRequireCsrf('habits.php');

$userId = (int) getAuthenticatedUserId();
$habitId = (int) ($_POST['habit_id'] ?? $_POST['id'] ?? 0);
$completionDate = $_POST['completion_date'] ?? getUserTodayDate($conn, $userId);

if ($habitId <= 0) {
    $_SESSION['error_message'] = 'Hábito inválido.';
    redirectBack();
}

$notes = $_POST['notes'] ?? null;
$mood = $_POST['mood'] ?? null;
$valueAchieved = isset($_POST['value_achieved']) ? (float) $_POST['value_achieved'] : null;

$habitCompletionService = new HabitCompletionService($conn);
$result = $habitCompletionService->toggleCompletion($habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);

$_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
redirectBack();

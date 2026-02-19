<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/habits/HabitAccessService.php';

actionRequireLoggedIn();
actionRequirePost('habits.php');

$userId = (int) getUserId();
$habitId = (int) ($_POST['habit_id'] ?? 0);
$operation = $_POST['operation'] ?? 'archive';

if ($habitId <= 0) {
    actionFlashAndRedirect('error_message', 'Hábito inválido.', '../public/habits.php');
}

$habitAccessService = new HabitAccessService($conn);
if (!$habitAccessService->userOwnsHabit($habitId, $userId)) {
    actionFlashAndRedirect('error_message', 'Você não tem permissão para modificar este hábito.', '../public/habits.php');
}

if ($operation === 'restore') {
    $updateStmt = $conn->prepare('UPDATE habits SET archived_at = NULL, is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
    $updateStmt->bind_param('ii', $habitId, $userId);

    if ($updateStmt->execute()) {
        actionFlashAndRedirect('success_message', 'Hábito restaurado com sucesso!', '../public/habits.php');
    }

    actionFlashAndRedirect('error_message', 'Erro ao restaurar hábito. Tente novamente.', '../public/habits.php');
} else {
    $updateStmt = $conn->prepare('UPDATE habits SET archived_at = CURRENT_TIMESTAMP, is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
    $updateStmt->bind_param('ii', $habitId, $userId);

    if ($updateStmt->execute()) {
        actionFlashAndRedirect('success_message', 'Hábito arquivado com sucesso!', '../public/habits.php');
    }

    actionFlashAndRedirect('error_message', 'Erro ao arquivar hábito. Tente novamente.', '../public/habits.php');
}

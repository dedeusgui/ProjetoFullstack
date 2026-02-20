<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/habits/HabitAccessService.php';

actionRequireLoggedIn();
actionRequirePost('habits.php');
actionRequireCsrf('habits.php');

$userId = (int) getUserId();
$habitId = (int) ($_POST['habit_id'] ?? $_POST['id'] ?? 0);

if ($habitId <= 0) {
    actionFlashAndRedirect('error_message', 'Hábito inválido.', '../public/habits.php');
}

$habitAccessService = new HabitAccessService($conn);
if (!$habitAccessService->userOwnsHabit($habitId, $userId)) {
    actionFlashAndRedirect('error_message', 'Você não tem permissão para deletar este hábito.', '../public/habits.php');
}

// Deletar hábito permanentemente
// As conclusões serão deletadas automaticamente por causa do CASCADE
$deleteStmt = $conn->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
$deleteStmt->bind_param("ii", $habitId, $userId);

if ($deleteStmt->execute()) {
    actionFlashAndRedirect('success_message', 'Hábito deletado com sucesso!', '../public/habits.php');
}

actionFlashAndRedirect('error_message', 'Erro ao deletar hábito. Tente novamente.', '../public/habits.php');
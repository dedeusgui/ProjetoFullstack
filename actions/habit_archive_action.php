<?php
require_once '../config/bootstrap.php';
bootApp();

if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/habits.php');
    exit;
}

$userId = getUserId();
$habitId = intval($_POST['habit_id'] ?? 0);
$operation = $_POST['operation'] ?? 'archive';

if ($habitId <= 0) {
    $_SESSION['error_message'] = 'Hábito inválido.';
    header('Location: ../public/habits.php');
    exit;
}

$stmt = $conn->prepare('SELECT id FROM habits WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $habitId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Você não tem permissão para modificar este hábito.';
    header('Location: ../public/habits.php');
    exit;
}

if ($operation === 'restore') {
    $updateStmt = $conn->prepare('UPDATE habits SET archived_at = NULL, is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
    $updateStmt->bind_param('ii', $habitId, $userId);

    if ($updateStmt->execute()) {
        $_SESSION['success_message'] = 'Hábito restaurado com sucesso!';
    } else {
        $_SESSION['error_message'] = 'Erro ao restaurar hábito. Tente novamente.';
    }
} else {
    $updateStmt = $conn->prepare('UPDATE habits SET archived_at = CURRENT_TIMESTAMP, is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?');
    $updateStmt->bind_param('ii', $habitId, $userId);

    if ($updateStmt->execute()) {
        $_SESSION['success_message'] = 'Hábito arquivado com sucesso!';
    } else {
        $_SESSION['error_message'] = 'Erro ao arquivar hábito. Tente novamente.';
    }
}

header('Location: ../public/habits.php');
exit;

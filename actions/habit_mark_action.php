<?php
session_start();
require_once '../config/conexao.php';
require_once '../config/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/habits.php');
    exit;
}

$userId = getUserId();

// Pegar ID do hábito
$habitId = intval($_POST['habit_id'] ?? $_POST['id'] ?? 0);
$completionDate = $_POST['completion_date'] ?? date('Y-m-d');

if ($habitId <= 0) {
    $_SESSION['error_message'] = 'Hábito inválido.';
    header('Location: ../public/habits.php');
    exit;
}

// Verificar se o hábito pertence ao usuário logado
$stmt = $conn->prepare("SELECT id FROM habits WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $habitId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Você não tem permissão para modificar este hábito.';
    header('Location: ../public/habits.php');
    exit;
}

// Verificar se já existe conclusão para esta data
$checkStmt = $conn->prepare("
    SELECT id FROM habit_completions 
    WHERE habit_id = ? AND user_id = ? AND completion_date = ?
");
$checkStmt->bind_param("iis", $habitId, $userId, $completionDate);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Já existe conclusão - DESMARCAR usando stored procedure
    $uncompleteSql = "CALL sp_uncomplete_habit(?, ?)";
    $uncompleteStmt = $conn->prepare($uncompleteSql);
    $uncompleteStmt->bind_param("is", $habitId, $completionDate);
    
    if ($uncompleteStmt->execute()) {
        $_SESSION['success_message'] = 'Conclusão removida com sucesso!';
    } else {
        $_SESSION['error_message'] = 'Erro ao remover conclusão. Tente novamente.';
    }
} else {
    // Não existe conclusão - MARCAR usando stored procedure
    $notes = $_POST['notes'] ?? null;
    $mood = $_POST['mood'] ?? null;
    $valueAchieved = isset($_POST['value_achieved']) ? floatval($_POST['value_achieved']) : null;
    
    $completeSql = "CALL sp_complete_habit(?, ?, ?, ?, ?, ?)";
    $completeStmt = $conn->prepare($completeSql);
    $completeStmt->bind_param(
        "iisdss",
        $habitId,
        $userId,
        $completionDate,
        $valueAchieved,
        $notes,
        $mood
    );
    
    if ($completeStmt->execute()) {
        $_SESSION['success_message'] = 'Hábito marcado como concluído!';
    } else {
        $_SESSION['error_message'] = 'Erro ao marcar conclusão. Tente novamente.';
    }
}

// Redirecionar de volta
$referer = $_SERVER['HTTP_REFERER'] ?? '../public/habits.php';
header('Location: ' . $referer);
exit;
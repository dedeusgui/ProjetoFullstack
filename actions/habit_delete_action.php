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
    $_SESSION['error_message'] = 'Você não tem permissão para deletar este hábito.';
    header('Location: ../public/habits.php');
    exit;
}

// Deletar hábito permanentemente
// As conclusões serão deletadas automaticamente por causa do CASCADE
$deleteStmt = $conn->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
$deleteStmt->bind_param("ii", $habitId, $userId);

if ($deleteStmt->execute()) {
    $_SESSION['success_message'] = 'Hábito deletado com sucesso!';
} else {
    $_SESSION['error_message'] = 'Erro ao deletar hábito. Tente novamente.';
}

header('Location: ../public/habits.php');
exit;
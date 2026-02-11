<?php
session_start();
require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';

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

// Pegar dados do formulário
$title = trim($_POST['title'] ?? $_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$timeOfDay = trim($_POST['time'] ?? $_POST['time_of_day'] ?? '');
$color = trim($_POST['color'] ?? '#4a74ff');
$icon = trim($_POST['icon'] ?? '');
$goalType = trim($_POST['goal_type'] ?? 'completion');
$goalValue = intval($_POST['goal_value'] ?? 1);
$goalUnit = trim($_POST['goal_unit'] ?? '');

// Validar campos obrigatórios
if (empty($title)) {
    $_SESSION['error_message'] = 'O título do hábito é obrigatório.';
    header('Location: ../public/habits.php');
    exit;
}

if (empty($category)) {
    $_SESSION['error_message'] = 'A categoria é obrigatória.';
    header('Location: ../public/habits.php');
    exit;
}

if (empty($timeOfDay)) {
    $_SESSION['error_message'] = 'O período do dia é obrigatório.';
    header('Location: ../public/habits.php');
    exit;
}

// Mapear time_of_day de PT-BR para EN (se necessário)
$timeOfDayEN = mapTimeOfDay($timeOfDay);

// Buscar category_id pelo nome
$categoryId = getCategoryIdByName($conn, $category);

if (!$categoryId) {
    $_SESSION['error_message'] = 'Categoria inválida.';
    header('Location: ../public/habits.php');
    exit;
}

// Inserir hábito no banco
$stmt = $conn->prepare("
    INSERT INTO habits (
        user_id,
        category_id,
        title,
        description,
        icon,
        color,
        time_of_day,
        goal_type,
        goal_value,
        goal_unit,
        start_date,
        is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 1)
");

$stmt->bind_param(
    "iissssssss",
    $userId,
    $categoryId,
    $title,
    $description,
    $icon,
    $color,
    $timeOfDayEN,
    $goalType,
    $goalValue,
    $goalUnit
);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Hábito criado com sucesso!';
} else {
    $_SESSION['error_message'] = 'Erro ao criar hábito. Tente novamente.';
}

header('Location: ../public/habits.php');
exit;
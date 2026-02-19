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
$habitId = intval($_POST['habit_id'] ?? $_POST['id'] ?? 0);

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
    $_SESSION['error_message'] = 'Você não tem permissão para editar este hábito.';
    header('Location: ../public/habits.php');
    exit;
}

$title = trim($_POST['title'] ?? $_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$timeOfDay = trim($_POST['time'] ?? $_POST['time_of_day'] ?? '');
$color = trim($_POST['color'] ?? '#4a74ff');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#4a74ff';
}
$icon = trim($_POST['icon'] ?? '');
$frequency = trim($_POST['frequency'] ?? 'daily');
$targetDays = $_POST['target_days'] ?? [];
$goalType = trim($_POST['goal_type'] ?? 'completion');
$goalValue = max(1, intval($_POST['goal_value'] ?? 1));
$goalUnit = trim($_POST['goal_unit'] ?? '');

if (empty($title) || empty($category) || empty($timeOfDay)) {
    $_SESSION['error_message'] = 'Preencha título, categoria e período do dia.';
    header('Location: ../public/habits.php');
    exit;
}

if (!in_array($frequency, ['daily', 'weekly', 'custom'], true)) {
    $frequency = 'daily';
}

if (!in_array($goalType, ['completion', 'quantity', 'duration'], true)) {
    $goalType = 'completion';
}

$targetDaysValues = [];
if (is_array($targetDays)) {
    foreach ($targetDays as $day) {
        $dayInt = intval($day);
        if ($dayInt >= 0 && $dayInt <= 6) {
            $targetDaysValues[] = $dayInt;
        }
    }
    $targetDaysValues = array_values(array_unique($targetDaysValues));
}

if (($frequency === 'weekly' || $frequency === 'custom') && count($targetDaysValues) === 0) {
    $_SESSION['error_message'] = 'Selecione pelo menos um dia da semana para frequência semanal/customizada.';
    header('Location: ../public/habits.php');
    exit;
}

$targetDaysJson = count($targetDaysValues) > 0 ? json_encode($targetDaysValues) : null;
$timeOfDayEN = mapTimeOfDay($timeOfDay);
$categoryId = getCategoryIdByName($conn, $category);

if (!$categoryId) {
    $_SESSION['error_message'] = 'Categoria inválida.';
    header('Location: ../public/habits.php');
    exit;
}

$updateStmt = $conn->prepare('UPDATE habits SET
        category_id = ?,
        title = ?,
        description = ?,
        icon = ?,
        color = ?,
        frequency = ?,
        target_days = ?,
        time_of_day = ?,
        goal_type = ?,
        goal_value = ?,
        goal_unit = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ? AND user_id = ?');

$updateStmt->bind_param(
    'issssssssisii',
    $categoryId,
    $title,
    $description,
    $icon,
    $color,
    $frequency,
    $targetDaysJson,
    $timeOfDayEN,
    $goalType,
    $goalValue,
    $goalUnit,
    $habitId,
    $userId
);

if ($updateStmt->execute()) {
    $_SESSION['success_message'] = 'Hábito atualizado com sucesso!';
} else {
    $_SESSION['error_message'] = 'Erro ao atualizar hábito. Tente novamente.';
}

header('Location: ../public/habits.php');
exit;

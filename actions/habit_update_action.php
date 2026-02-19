<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/habits/HabitInputSanitizer.php';

actionRequireLoggedIn();
actionRequirePost('habits.php');

$userId = (int) getUserId();
$habitId = (int) ($_POST['habit_id'] ?? $_POST['id'] ?? 0);
if ($habitId <= 0) {
    actionFlashAndRedirect('error_message', 'Hábito inválido.', '../public/habits.php');
}

$stmt = $conn->prepare('SELECT id FROM habits WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $habitId, $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    actionFlashAndRedirect('error_message', 'Você não tem permissão para editar este hábito.', '../public/habits.php');
}

$input = HabitInputSanitizer::fromRequest($_POST);
if (!empty($input['errors'])) {
    actionFlashAndRedirect('error_message', $input['errors'][0], '../public/habits.php');
}

$data = $input['data'];
$categoryId = getCategoryIdByName($conn, $data['category']);
if (!$categoryId) {
    actionFlashAndRedirect('error_message', 'Categoria inválida.', '../public/habits.php');
}

$timeOfDayEN = mapTimeOfDay($data['time_of_day']);
$updateStmt = $conn->prepare("UPDATE habits SET
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
    WHERE id = ? AND user_id = ?");

$updateStmt->bind_param(
    'isssssssssisii',
    $categoryId,
    $data['title'],
    $data['description'],
    $data['icon'],
    $data['color'],
    $data['frequency'],
    $data['target_days_json'],
    $timeOfDayEN,
    $data['goal_type'],
    $data['goal_value'],
    $data['goal_unit'],
    $habitId,
    $userId
);

if ($updateStmt->execute()) {
    actionFlashAndRedirect('success_message', 'Hábito atualizado com sucesso!', '../public/habits.php');
}

actionFlashAndRedirect('error_message', 'Erro ao atualizar hábito. Tente novamente.', '../public/habits.php');

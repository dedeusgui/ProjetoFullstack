<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/habits/HabitInputSanitizer.php';

actionRequireLoggedIn();
actionRequirePost('habits.php');

$userId = (int) getUserId();
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
$stmt = $conn->prepare("INSERT INTO habits (
        user_id, category_id, title, description, icon, color,
        frequency, target_days, time_of_day, goal_type, goal_value, goal_unit,
        start_date, is_active, archived_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 1, NULL)");

$stmt->bind_param(
    'iissssssssis',
    $userId,
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
    $data['goal_unit']
);

if ($stmt->execute()) {
    actionFlashAndRedirect('success_message', 'Hábito criado com sucesso!', '../public/habits.php');
}

actionFlashAndRedirect('error_message', 'Erro ao criar hábito. Tente novamente.', '../public/habits.php');

<?php
session_start();

require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';

if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

$userId = (int) getUserId();
$userData = getCurrentUser($conn);

if (!$userData) {
    $_SESSION['error_message'] = 'Usuário não encontrado para exportação.';
    header('Location: ../public/dashboard.php');
    exit;
}

$today = date('Y-m-d');

$habitsStmt = $conn->prepare("\n    SELECT\n        h.title,\n        h.is_active,\n        h.total_completions,\n        h.current_streak,\n        CASE WHEN hc.id IS NOT NULL THEN 1 ELSE 0 END AS completed_today\n    FROM habits h\n    LEFT JOIN habit_completions hc\n        ON hc.habit_id = h.id\n        AND hc.user_id = h.user_id\n        AND hc.completion_date = ?\n    WHERE h.user_id = ?\n    ORDER BY h.created_at DESC\n");
$habitsStmt->bind_param('si', $today, $userId);
$habitsStmt->execute();
$habitsResult = $habitsStmt->get_result();

$habits = [];
while ($habit = $habitsResult->fetch_assoc()) {
    $habits[] = $habit;
}

$achievements = getUserAchievements($conn, $userId);
$unlockedAchievements = array_values(array_filter($achievements, function ($achievement) {
    return !empty($achievement['unlocked']);
}));

$habitsDoneToday = 0;
$activeHabitsCount = 0;
foreach ($habits as $habit) {
    if ((int) $habit['completed_today'] === 1) {
        $habitsDoneToday++;
    }

    if ((int) $habit['is_active'] === 1) {
        $activeHabitsCount++;
    }
}

$filename = 'resumo_dados_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($userData['name'])) . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM UTF-8 para abrir corretamente no Excel.
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, ['Resumo de dados do usuário']);
fputcsv($output, ['Nome', 'E-mail', 'Data de exportação']);
fputcsv($output, [$userData['name'], $userData['email'], date('d/m/Y H:i:s')]);
fputcsv($output, []);

fputcsv($output, ['Resumo geral']);
fputcsv($output, ['Hábitos cadastrados', 'Hábitos ativos', 'Fez hoje', 'Não fez hoje', 'Conquistas desbloqueadas']);
fputcsv($output, [
    count($habits),
    $activeHabitsCount,
    $habitsDoneToday,
    max(0, count($habits) - $habitsDoneToday),
    count($unlockedAchievements)
]);
fputcsv($output, []);

fputcsv($output, ['Resumo dos hábitos']);
fputcsv($output, ['Hábito', 'Status hoje', 'Ativo', 'Total de conclusões', 'Sequência atual']);

if (count($habits) === 0) {
    fputcsv($output, ['Nenhum hábito cadastrado', '-', '-', '-', '-']);
} else {
    foreach ($habits as $habit) {
        fputcsv($output, [
            $habit['title'],
            ((int) $habit['completed_today'] === 1) ? 'Fez hoje' : 'Não fez hoje',
            ((int) $habit['is_active'] === 1) ? 'Sim' : 'Não',
            (int) $habit['total_completions'],
            (int) $habit['current_streak']
        ]);
    }
}

fputcsv($output, []);
fputcsv($output, ['Conquistas desbloqueadas']);
fputcsv($output, ['Conquista', 'Raridade', 'Pontos', 'Data de desbloqueio']);

if (count($unlockedAchievements) === 0) {
    fputcsv($output, ['Nenhuma conquista desbloqueada', '-', '-', '-']);
} else {
    foreach ($unlockedAchievements as $achievement) {
        fputcsv($output, [
            $achievement['name'],
            ucfirst($achievement['rarity']),
            (int) $achievement['points'],
            !empty($achievement['date']) ? date('d/m/Y H:i', strtotime($achievement['date'])) : '-'
        ]);
    }
}

fclose($output);
exit;

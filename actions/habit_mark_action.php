<?php
session_start();
require_once '../config/conexao.php';
require_once '../config/auth.php';

function redirectBack(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '../public/habits.php';
    header('Location: ' . $referer);
    exit;
}

function clearProcedureResults(mysqli $conn): void
{
    while ($conn->more_results() && $conn->next_result()) {
        $result = $conn->use_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    }
}

function invalidateRecommendationSnapshot(mysqli $conn, int $userId): void
{
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_recommendations'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return;
    }

    $stmt = $conn->prepare('DELETE FROM user_recommendations WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

function uncompleteHabitFallback(mysqli $conn, int $habitId, string $completionDate): bool
{
    $conn->begin_transaction();

    try {
        $deleteStmt = $conn->prepare('DELETE FROM habit_completions WHERE habit_id = ? AND completion_date = ?');
        $deleteStmt->bind_param('is', $habitId, $completionDate);
        $deleteStmt->execute();

        $streakStmt = $conn->prepare('UPDATE habits SET current_streak = (
            SELECT COUNT(DISTINCT completion_date)
            FROM habit_completions
            WHERE habit_id = ?
              AND completion_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ), total_completions = GREATEST(0, total_completions - 1)
        WHERE id = ?');
        $streakStmt->bind_param('ii', $habitId, $habitId);
        $streakStmt->execute();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

function completeHabitFallback(
    mysqli $conn,
    int $habitId,
    int $userId,
    string $completionDate,
    ?float $valueAchieved,
    ?string $notes,
    ?string $mood
): bool {
    $conn->begin_transaction();

    try {
        $insertStmt = $conn->prepare('INSERT INTO habit_completions (habit_id, user_id, completion_date, value_achieved, notes, mood)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              value_achieved = VALUES(value_achieved),
              notes = VALUES(notes),
              mood = VALUES(mood),
              completed_at = CURRENT_TIMESTAMP');
        $insertStmt->bind_param('iisdss', $habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);
        $insertStmt->execute();

        $yesterday = date('Y-m-d', strtotime($completionDate . ' -1 day'));
        $checkStmt = $conn->prepare('SELECT 1 FROM habit_completions WHERE habit_id = ? AND completion_date = ? LIMIT 1');
        $checkStmt->bind_param('is', $habitId, $yesterday);
        $checkStmt->execute();
        $hasYesterday = $checkStmt->get_result()->num_rows > 0;

        if ($hasYesterday) {
            $updateStmt = $conn->prepare('UPDATE habits
                SET current_streak = current_streak + 1,
                    total_completions = total_completions + 1,
                    longest_streak = GREATEST(longest_streak, current_streak + 1)
                WHERE id = ?');
            $updateStmt->bind_param('i', $habitId);
        } else {
            $updateStmt = $conn->prepare('UPDATE habits
                SET current_streak = 1,
                    total_completions = total_completions + 1,
                    longest_streak = GREATEST(longest_streak, 1)
                WHERE id = ?');
            $updateStmt->bind_param('i', $habitId);
        }
        $updateStmt->execute();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

// Verificar autenticação
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
$completionDate = $_POST['completion_date'] ?? date('Y-m-d');

if ($habitId <= 0) {
    $_SESSION['error_message'] = 'Hábito inválido.';
    redirectBack();
}

$stmt = $conn->prepare('SELECT id FROM habits WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $habitId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Você não tem permissão para modificar este hábito.';
    redirectBack();
}

$checkStmt = $conn->prepare('SELECT id FROM habit_completions WHERE habit_id = ? AND user_id = ? AND completion_date = ?');
$checkStmt->bind_param('iis', $habitId, $userId, $completionDate);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $uncompleteStmt = $conn->prepare('CALL sp_uncomplete_habit(?, ?)');
    $uncompleteStmt->bind_param('is', $habitId, $completionDate);

    $removed = $uncompleteStmt->execute();
    clearProcedureResults($conn);

    if (!$removed) {
        $removed = uncompleteHabitFallback($conn, $habitId, $completionDate);
    }

    if ($removed) {
        invalidateRecommendationSnapshot($conn, $userId);
        $_SESSION['success_message'] = 'Conclusão removida com sucesso!';
    } else {
        $_SESSION['error_message'] = 'Erro ao remover conclusão. Tente novamente.';
    }
} else {
    $notes = $_POST['notes'] ?? null;
    $mood = $_POST['mood'] ?? null;
    $valueAchieved = isset($_POST['value_achieved']) ? floatval($_POST['value_achieved']) : null;

    $completeStmt = $conn->prepare('CALL sp_complete_habit(?, ?, ?, ?, ?, ?)');
    $completeStmt->bind_param('iisdss', $habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);

    $completed = $completeStmt->execute();
    clearProcedureResults($conn);

    if (!$completed) {
        $completed = completeHabitFallback($conn, $habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);
    }

    if ($completed) {
        invalidateRecommendationSnapshot($conn, $userId);
        $_SESSION['success_message'] = 'Hábito marcado como concluído!';
    } else {
        $_SESSION['error_message'] = 'Erro ao marcar conclusão. Tente novamente.';
    }
}

redirectBack();

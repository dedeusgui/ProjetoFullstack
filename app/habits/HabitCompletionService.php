<?php

namespace App\Habits;

use App\Support\DateFormatter;

class HabitCompletionService
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function toggleCompletion(
        int $habitId,
        int $userId,
        string $completionDate,
        ?float $valueAchieved,
        ?string $notes,
        ?string $mood
    ): array {
        try {
        $habit = $this->fetchHabitForUser($habitId, $userId);
        if (!$habit) {
            return ['success' => false, 'message' => 'Você não tem permissão para modificar este hábito.'];
        }

        if (!empty($habit['archived_at']) || (int) $habit['is_active'] !== 1) {
            return ['success' => false, 'message' => 'Não é possível concluir um hábito arquivado.'];
        }

        if (!HabitSchedulePolicy::isScheduledForDate($habit, $completionDate)) {
            return ['success' => false, 'message' => 'Este hábito não está programado para essa data.'];
        }

        if ($this->hasCompletionOnDate($habitId, $userId, $completionDate)) {
            return $this->removeCompletion($habitId, $userId, $completionDate);
        }

        return $this->addCompletion($habit, $habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);
        } catch (\Throwable $exception) {
            if (\function_exists('appLogThrowable')) {
                \appLogThrowable($exception, ['service' => 'HabitCompletionService::toggleCompletion']);
            }
            return ['success' => false, 'message' => 'Erro ao processar conclusão do hábito. Tente novamente.'];
        }
    }

    private function fetchHabitForUser(int $habitId, int $userId): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, is_active, archived_at, frequency, target_days, start_date, end_date, goal_type FROM habits WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $habitId, $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    private function hasCompletionOnDate(int $habitId, int $userId, string $completionDate): bool
    {
        $stmt = $this->conn->prepare('SELECT id FROM habit_completions WHERE habit_id = ? AND user_id = ? AND completion_date = ?');
        $stmt->bind_param('iis', $habitId, $userId, $completionDate);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    private function removeCompletion(int $habitId, int $userId, string $completionDate): array
    {
        $uncompleteStmt = $this->conn->prepare('CALL sp_uncomplete_habit(?, ?)');
        $uncompleteStmt->bind_param('is', $habitId, $completionDate);

        $removed = $uncompleteStmt->execute();
        $this->clearProcedureResults();

        if (!$removed) {
            $removed = $this->uncompleteHabitFallback($habitId, $completionDate);
        }

        if (!$removed) {
            return ['success' => false, 'message' => 'Erro ao remover conclusão. Tente novamente.'];
        }

        $this->invalidateRecommendationSnapshot($userId);
        return ['success' => true, 'message' => 'Conclusão removida com sucesso!'];
    }

    private function addCompletion(
        array $habit,
        int $habitId,
        int $userId,
        string $completionDate,
        ?float $valueAchieved,
        ?string $notes,
        ?string $mood
    ): array {
        if (($habit['goal_type'] ?? 'completion') !== 'completion') {
            if ($valueAchieved === null || $valueAchieved <= 0) {
                return ['success' => false, 'message' => 'Informe o valor alcançado para concluir esse hábito.'];
            }
        }

        $completeStmt = $this->conn->prepare('CALL sp_complete_habit(?, ?, ?, ?, ?, ?)');
        $completeStmt->bind_param('iisdss', $habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);

        $completed = $completeStmt->execute();
        $this->clearProcedureResults();

        if (!$completed) {
            $completed = $this->completeHabitFallback($habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);
        }

        if (!$completed) {
            return ['success' => false, 'message' => 'Erro ao marcar conclusão. Tente novamente.'];
        }

        $this->invalidateRecommendationSnapshot($userId);

        $nextSearchDate = date('Y-m-d', strtotime($completionDate . ' +1 day'));
        $nextDueDate = HabitSchedulePolicy::getNextDueDate($habit, $nextSearchDate);
        $nextDueText = $nextDueDate ? DateFormatter::formatBr($nextDueDate) : 'sem próxima data';

        return [
            'success' => true,
            'message' => 'Hábito marcado como concluído! Próxima execução: ' . $nextDueText . '.'
        ];
    }

    private function clearProcedureResults(): void
    {
        while ($this->conn->more_results() && $this->conn->next_result()) {
            $result = $this->conn->use_result();
            if ($result instanceof \mysqli_result) {
                $result->free();
            }
        }
    }

    private function invalidateRecommendationSnapshot(int $userId): void
    {
        $tableCheck = $this->conn->query("SHOW TABLES LIKE 'user_recommendations'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return;
        }

        $stmt = $this->conn->prepare('DELETE FROM user_recommendations WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    private function uncompleteHabitFallback(int $habitId, string $completionDate): bool
    {
        $this->conn->begin_transaction();

        try {
            $deleteStmt = $this->conn->prepare('DELETE FROM habit_completions WHERE habit_id = ? AND completion_date = ?');
            $deleteStmt->bind_param('is', $habitId, $completionDate);
            $deleteStmt->execute();

            $streakStmt = $this->conn->prepare('UPDATE habits SET current_streak = (
                SELECT COUNT(DISTINCT completion_date)
                FROM habit_completions
                WHERE habit_id = ?
                  AND completion_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ), total_completions = GREATEST(0, total_completions - 1)
            WHERE id = ?');
            $streakStmt->bind_param('ii', $habitId, $habitId);
            $streakStmt->execute();

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            try {
                $this->conn->rollback();
            } catch (\Throwable $rollbackException) {
            }
            if (\function_exists('appLogThrowable')) {
                \appLogThrowable($e, ['service' => 'HabitCompletionService::uncompleteHabitFallback']);
            }
            return false;
        }
    }

    private function completeHabitFallback(
        int $habitId,
        int $userId,
        string $completionDate,
        ?float $valueAchieved,
        ?string $notes,
        ?string $mood
    ): bool {
        $this->conn->begin_transaction();

        try {
            $insertStmt = $this->conn->prepare('INSERT INTO habit_completions (habit_id, user_id, completion_date, value_achieved, notes, mood)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  value_achieved = VALUES(value_achieved),
                  notes = VALUES(notes),
                  mood = VALUES(mood),
                  completed_at = CURRENT_TIMESTAMP');
            $insertStmt->bind_param('iisdss', $habitId, $userId, $completionDate, $valueAchieved, $notes, $mood);
            $insertStmt->execute();

            $yesterday = date('Y-m-d', strtotime($completionDate . ' -1 day'));
            $checkStmt = $this->conn->prepare('SELECT 1 FROM habit_completions WHERE habit_id = ? AND completion_date = ? LIMIT 1');
            $checkStmt->bind_param('is', $habitId, $yesterday);
            $checkStmt->execute();
            $hasYesterday = $checkStmt->get_result()->num_rows > 0;

            if ($hasYesterday) {
                $updateStmt = $this->conn->prepare('UPDATE habits
                    SET current_streak = current_streak + 1,
                        total_completions = total_completions + 1,
                        longest_streak = GREATEST(longest_streak, current_streak + 1)
                    WHERE id = ?');
            } else {
                $updateStmt = $this->conn->prepare('UPDATE habits
                    SET current_streak = 1,
                        total_completions = total_completions + 1,
                        longest_streak = GREATEST(longest_streak, 1)
                    WHERE id = ?');
            }
            $updateStmt->bind_param('i', $habitId);
            $updateStmt->execute();

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            try {
                $this->conn->rollback();
            } catch (\Throwable $rollbackException) {
            }
            if (\function_exists('appLogThrowable')) {
                \appLogThrowable($e, ['service' => 'HabitCompletionService::completeHabitFallback']);
            }
            return false;
        }
    }
}

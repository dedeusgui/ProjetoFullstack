<?php

namespace App\Profile;

use App\Achievements\AchievementService;

final class UserDataCsvExportService
{
    public function __construct(private \mysqli $conn)
    {
    }

    public function build(int $userId): array
    {
        $userData = $this->findActiveUser($userId);
        if ($userData === null) {
            return [
                'success' => false,
                'message' => 'Usuario nao encontrado para exportacao.',
            ];
        }

        $today = date('Y-m-d');
        $habits = $this->fetchHabitsSummary($userId, $today);

        $achievementService = new AchievementService($this->conn);
        $achievements = $achievementService->syncUserAchievements($userId);
        $unlockedAchievements = array_values(array_filter(
            $achievements,
            static fn (array $achievement): bool => !empty($achievement['unlocked'])
        ));

        $habitsDoneToday = 0;
        $activeHabitsCount = 0;
        foreach ($habits as $habit) {
            if ((int) ($habit['completed_today'] ?? 0) === 1) {
                $habitsDoneToday++;
            }

            if ((int) ($habit['is_active'] ?? 0) === 1) {
                $activeHabitsCount++;
            }
        }

        $filename = 'resumo_dados_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower((string) $userData['name'])) . '_' . date('Ymd_His') . '.csv';
        $content = $this->buildCsvContent($userData, $habits, $unlockedAchievements, $habitsDoneToday, $activeHabitsCount);

        return [
            'success' => true,
            'filename' => $filename,
            'content' => $content,
        ];
    }

    private function findActiveUser(int $userId): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, name, email FROM users WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    private function fetchHabitsSummary(int $userId, string $today): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                h.title,
                h.is_active,
                h.total_completions,
                h.current_streak,
                CASE WHEN hc.id IS NOT NULL THEN 1 ELSE 0 END AS completed_today
            FROM habits h
            LEFT JOIN habit_completions hc
                ON hc.habit_id = h.id
                AND hc.user_id = h.user_id
                AND hc.completion_date = ?
            WHERE h.user_id = ?
            ORDER BY h.created_at DESC
        ");
        $stmt->bind_param('si', $today, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function buildCsvContent(
        array $userData,
        array $habits,
        array $unlockedAchievements,
        int $habitsDoneToday,
        int $activeHabitsCount
    ): string {
        $output = fopen('php://temp', 'w+');
        if ($output === false) {
            throw new \RuntimeException('Failed to create CSV output stream.');
        }

        fwrite($output, "\xEF\xBB\xBF");

        $this->writeCsvRow($output, ['Resumo de dados do usuario']);
        $this->writeCsvRow($output, ['Nome', 'E-mail', 'Data de exportacao']);
        $this->writeCsvRow($output, [(string) ($userData['name'] ?? ''), (string) ($userData['email'] ?? ''), date('d/m/Y H:i:s')]);
        $this->writeCsvRow($output, []);

        $this->writeCsvRow($output, ['Resumo geral']);
        $this->writeCsvRow($output, ['Habitos cadastrados', 'Habitos ativos', 'Fez hoje', 'Nao fez hoje', 'Conquistas desbloqueadas']);
        $this->writeCsvRow($output, [
            count($habits),
            $activeHabitsCount,
            $habitsDoneToday,
            max(0, count($habits) - $habitsDoneToday),
            count($unlockedAchievements),
        ]);
        $this->writeCsvRow($output, []);

        $this->writeCsvRow($output, ['Resumo dos habitos']);
        $this->writeCsvRow($output, ['Habito', 'Status hoje', 'Ativo', 'Total de conclusoes', 'Sequencia atual']);

        if (count($habits) === 0) {
            $this->writeCsvRow($output, ['Nenhum habito cadastrado', '-', '-', '-', '-']);
        } else {
            foreach ($habits as $habit) {
                $this->writeCsvRow($output, [
                    (string) ($habit['title'] ?? ''),
                    ((int) ($habit['completed_today'] ?? 0) === 1) ? 'Fez hoje' : 'Nao fez hoje',
                    ((int) ($habit['is_active'] ?? 0) === 1) ? 'Sim' : 'Nao',
                    (int) ($habit['total_completions'] ?? 0),
                    (int) ($habit['current_streak'] ?? 0),
                ]);
            }
        }

        $this->writeCsvRow($output, []);
        $this->writeCsvRow($output, ['Conquistas desbloqueadas']);
        $this->writeCsvRow($output, ['Conquista', 'Raridade', 'Pontos', 'Data de desbloqueio']);

        if (count($unlockedAchievements) === 0) {
            $this->writeCsvRow($output, ['Nenhuma conquista desbloqueada', '-', '-', '-']);
        } else {
            foreach ($unlockedAchievements as $achievement) {
                $this->writeCsvRow($output, [
                    (string) ($achievement['name'] ?? ''),
                    ucfirst((string) ($achievement['rarity'] ?? '')),
                    (int) ($achievement['points'] ?? 0),
                    !empty($achievement['date']) ? date('d/m/Y H:i', strtotime((string) $achievement['date'])) : '-',
                ]);
            }
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return is_string($content) ? $content : '';
    }

    /**
     * Pass explicit CSV escape arg to avoid PHP 8.4+/8.5 deprecation warnings.
     */
    private function writeCsvRow($output, array $row): void
    {
        fputcsv($output, $row, ',', '"', '');
    }
}

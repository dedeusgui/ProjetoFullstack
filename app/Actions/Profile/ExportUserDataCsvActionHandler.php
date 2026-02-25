<?php

namespace App\Actions\Profile;

use App\Actions\ActionResponse;
use App\Profile\UserDataCsvExportService;

final class ExportUserDataCsvActionHandler
{
    public function handle(\mysqli $conn, array $server, array &$session): ActionResponse
    {
        if (!$this->isLoggedIn($session)) {
            return ActionResponse::redirect('../public/login.php');
        }

        $userId = (int) ($session['user_id'] ?? 0);
        $service = new UserDataCsvExportService($conn);
        $result = $service->build($userId);

        if (empty($result['success'])) {
            return ActionResponse::redirect('../public/dashboard.php', [
                'error_message' => (string) ($result['message'] ?? 'Erro ao exportar dados.'),
            ]);
        }

        return ActionResponse::csv(
            (string) ($result['filename'] ?? 'resumo_dados.csv'),
            (string) ($result['content'] ?? '')
        );
    }

    private function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']) && !empty($session['user_id']);
    }
}

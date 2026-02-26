<?php

namespace App\Actions\Api;

use App\Actions\ActionResponse;
use App\Api\Internal\HabitsApiPayloadBuilder;
use App\Support\RequestContext;

final class HabitsApiGetActionHandler
{
    public function handle(\mysqli $conn, array $get, array $server, array &$session): ActionResponse
    {
        if (!$this->isLoggedIn($session)) {
            return ActionResponse::json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
                'error_code' => 'unauthorized',
                'request_id' => RequestContext::getRequestId(),
            ], 401);
        }

        $userId = (int) ($session['user_id'] ?? 0);
        $scope = ApiQueryParamNormalizer::normalizeHabitsScope($get['scope'] ?? 'all');

        return ActionResponse::json(HabitsApiPayloadBuilder::build($conn, $userId, $scope));
    }

    private function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']) && !empty($session['user_id']);
    }
}


<?php

namespace App\Actions\Api;

use App\Actions\ActionResponse;
use App\Api\Internal\StatsApiPayloadBuilder;
use App\Support\RequestContext;

final class StatsApiGetActionHandler
{
    public function handle(\mysqli $conn, array $get, array $server, array &$session): ActionResponse
    {
        if (!$this->isLoggedIn($session)) {
            return ActionResponse::json([
                'success' => false,
                'message' => 'Usuario nao autenticado.',
                'error_code' => 'unauthorized',
                'request_id' => RequestContext::getRequestId(),
            ], 401);
        }

        $userId = (int) ($session['user_id'] ?? 0);
        $view = ApiQueryParamNormalizer::normalizeStatsView($get['view'] ?? 'dashboard');

        return ActionResponse::json(StatsApiPayloadBuilder::build($conn, $userId, $view));
    }

    private function isLoggedIn(array $session): bool
    {
        return isset($session['user_id']) && !empty($session['user_id']);
    }
}

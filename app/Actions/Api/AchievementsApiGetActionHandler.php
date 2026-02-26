<?php

namespace App\Actions\Api;

use App\Actions\ActionResponse;
use App\Api\Internal\AchievementsApiPayloadBuilder;
use App\Support\RequestContext;

final class AchievementsApiGetActionHandler
{
    public function handle(\mysqli $conn, array $session): ActionResponse
    {
        if (!isset($session['user_id']) || empty($session['user_id'])) {
            return ActionResponse::json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
                'error_code' => 'unauthorized',
                'request_id' => RequestContext::getRequestId(),
            ], 401);
        }

        return ActionResponse::json(AchievementsApiPayloadBuilder::build($conn, (int) $session['user_id']));
    }
}

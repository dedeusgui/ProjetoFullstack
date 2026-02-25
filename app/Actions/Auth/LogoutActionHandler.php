<?php

namespace App\Actions\Auth;

use App\Actions\ActionResponse;

final class LogoutActionHandler
{
    public function handle(array &$session): ActionResponse
    {
        $session = [];

        return ActionResponse::redirect('../public/login.php');
    }
}

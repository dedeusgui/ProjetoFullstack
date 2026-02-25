<?php

declare(strict_types=1);

namespace Tests\Action\Profile;

use App\Actions\Profile\ResetAppearanceActionHandler;
use Tests\Support\ActionTestCase;

final class ResetAppearanceActionHandlerTest extends ActionTestCase
{
    public function testResetAppearanceRequiresAuthentication(): void
    {
        $handler = new ResetAppearanceActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'POST'], [], []);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
    }

    public function testResetAppearanceRejectsInvalidCsrf(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new ResetAppearanceActionHandler();
        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['return_to' => 'habits.php', 'csrf_token' => 'bad'],
            ['user_id' => $userId, 'csrf_token' => 'good']
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/dashboard.php');
        self::assertArrayHasKey('error_message', $response->getFlash());
    }

    public function testResetAppearanceUsesAllowedReturnPathAndSuccessFlash(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new ResetAppearanceActionHandler();
        $token = 'csrf-reset-appearance';

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['return_to' => 'habits.php', 'csrf_token' => $token],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertArrayHasKey('success_message', $response->getFlash());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Action\Habits;

use App\Actions\Habits\HabitDeleteActionHandler;
use Tests\Support\ActionTestCase;

final class HabitDeleteActionHandlerTest extends ActionTestCase
{
    public function testRedirectsToLoginWhenUserIsNotAuthenticated(): void
    {
        $handler = new HabitDeleteActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'POST'], ['csrf_token' => 'x'], ['csrf_token' => 'x']);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
    }

    public function testRejectsInvalidCsrfAndPreservesHabit(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId);
        $handler = new HabitDeleteActionHandler();

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['habit_id' => $habitId, 'csrf_token' => 'bad'],
            ['user_id' => $userId, 'csrf_token' => 'good']
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertArrayHasKey('error_message', $response->getFlash());
        self::assertNotNull($this->db()->fetchOne('SELECT id FROM habits WHERE id = ' . (int) $habitId));
    }

    public function testDeleteSupportsIdAliasAndDeletesHabit(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId);
        $handler = new HabitDeleteActionHandler();
        $token = 'csrf-delete';

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['id' => $habitId, 'csrf_token' => $token],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Hábito deletado com sucesso!', $response->getFlash()['success_message'] ?? null);
        self::assertNull($this->db()->fetchOne('SELECT id FROM habits WHERE id = ' . (int) $habitId));
    }
}


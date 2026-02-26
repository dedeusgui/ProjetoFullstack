<?php

declare(strict_types=1);

namespace Tests\Action\Habits;

use App\Actions\Habits\HabitCreateActionHandler;
use App\Actions\Habits\HabitToggleCompletionActionHandler;
use App\Actions\Habits\HabitUpdateActionHandler;
use Tests\Support\ActionTestCase;

final class HabitActionHandlersTest extends ActionTestCase
{
    public function testCreateRedirectsToLoginWhenUserIsNotAuthenticated(): void
    {
        $handler = new HabitCreateActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'POST'], ['csrf_token' => 'x'], ['csrf_token' => 'x']);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
    }

    public function testCreateRejectsInvalidCsrfAndSetsErrorFlash(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new HabitCreateActionHandler();

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            $this->validCreatePayload() + ['csrf_token' => 'bad-token'],
            ['user_id' => $userId, 'csrf_token' => 'good-token']
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertArrayHasKey('error_message', $response->getFlash());
        self::assertCount(0, $this->db()->fetchAll('SELECT id FROM habits'));
    }

    public function testCreatePersistsHabitAndReturnsSuccessFlash(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new HabitCreateActionHandler();
        $token = 'csrf-create';

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            $this->validCreatePayload() + ['csrf_token' => $token],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Hábito criado com sucesso!', $response->getFlash()['success_message'] ?? null);

        $habit = $this->db()->fetchOne('SELECT user_id, title, frequency, time_of_day FROM habits LIMIT 1');
        self::assertNotNull($habit);
        self::assertSame((string) $userId, (string) $habit['user_id']);
        self::assertSame('Drink Water', $habit['title']);
        self::assertSame('daily', $habit['frequency']);
        self::assertSame('afternoon', $habit['time_of_day']);
    }

    public function testUpdateRejectsUnauthorizedUser(): void
    {
        $ownerId = $this->fixtures->createUser(['_counter' => 1]);
        $otherUserId = $this->fixtures->createUser(['_counter' => 2]);
        $habitId = $this->fixtures->createHabit($ownerId, ['title' => 'Owner Habit']);
        $handler = new HabitUpdateActionHandler();
        $token = 'csrf-update';

        $payload = $this->validCreatePayload() + [
            'csrf_token' => $token,
            'habit_id' => $habitId,
            'title' => 'Hacked Title',
        ];

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload, ['user_id' => $otherUserId, 'csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Você não tem permissão para editar este hábito.', $response->getFlash()['error_message'] ?? null);

        $habit = $this->db()->fetchOne('SELECT title FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('Owner Habit', $habit['title'] ?? null);
    }

    public function testUpdatePersistsChangesForOwner(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'title' => 'Old Title',
            'frequency' => 'daily',
            'target_days' => null,
        ]);
        $handler = new HabitUpdateActionHandler();
        $token = 'csrf-update-ok';

        $payload = [
            'habit_id' => $habitId,
            'csrf_token' => $token,
            'title' => 'Updated Habit',
            'description' => 'Updated description',
            'category' => 'Outros',
            'time' => 'Noite',
            'color' => '#112233',
            'icon' => 'moon',
            'frequency' => 'weekly',
            'target_days' => [1, 3],
            'goal_type' => 'completion',
            'goal_value' => 1,
            'goal_unit' => '',
        ];

        $this->setRequest(['REQUEST_METHOD' => 'POST'], $payload, ['user_id' => $userId, 'csrf_token' => $token]);

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Hábito atualizado com sucesso!', $response->getFlash()['success_message'] ?? null);

        $habit = $this->db()->fetchOne('SELECT title, frequency, target_days, time_of_day FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('Updated Habit', $habit['title'] ?? null);
        self::assertSame('weekly', $habit['frequency'] ?? null);
        self::assertSame('[1,3]', $habit['target_days'] ?? null);
        self::assertSame('evening', $habit['time_of_day'] ?? null);
    }

    public function testToggleAddsThenRemovesCompletion(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'frequency' => 'daily',
            'goal_type' => 'completion',
        ]);
        $handler = new HabitToggleCompletionActionHandler();
        $token = 'csrf-toggle';

        $this->setRequest(
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST' => 'localhost',
                'HTTP_REFERER' => 'http://localhost/public/habits.php?tab=today',
            ],
            [
                'habit_id' => $habitId,
                'completion_date' => '2026-02-10',
                'csrf_token' => $token,
            ],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $responseAdd = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);
        $this->assertRedirect($responseAdd, '/public/habits.php?tab=today');
        self::assertArrayHasKey('success_message', $responseAdd->getFlash());
        self::assertSame('1', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions')['c'] ?? '0'));

        $responseRemove = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);
        $this->assertRedirect($responseRemove, '/public/habits.php?tab=today');
        self::assertArrayHasKey('success_message', $responseRemove->getFlash());
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions')['c'] ?? '1'));
    }

    public function testToggleRejectsUnscheduledDate(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'frequency' => 'weekly',
            'target_days' => json_encode([1]),
        ]);
        $handler = new HabitToggleCompletionActionHandler();
        $token = 'csrf-toggle-unscheduled';

        $this->setRequest(
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST' => 'localhost',
                'HTTP_REFERER' => 'http://localhost/public/habits.php',
            ],
            [
                'habit_id' => $habitId,
                'completion_date' => '2026-02-03',
                'csrf_token' => $token,
            ],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '/public/habits.php');
        self::assertArrayHasKey('error_message', $response->getFlash());
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habit_completions')['c'] ?? '1'));
    }

    public function testToggleRejectsArchivedHabit(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'is_active' => 0,
            'archived_at' => '2026-02-01 10:00:00',
        ]);
        $handler = new HabitToggleCompletionActionHandler();
        $token = 'csrf-toggle-archived';

        $this->setRequest(
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST' => 'localhost',
                'HTTP_REFERER' => 'http://localhost/public/habits.php',
            ],
            [
                'habit_id' => $habitId,
                'completion_date' => '2026-02-10',
                'csrf_token' => $token,
            ],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '/public/habits.php');
        self::assertArrayHasKey('error_message', $response->getFlash());
    }

    public function testToggleFallsBackToDefaultRedirectForExternalReferer(): void
    {
        $userId = $this->fixtures->createUser();
        $handler = new HabitToggleCompletionActionHandler();
        $token = 'csrf-toggle-referer';

        $this->setRequest(
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST' => 'localhost',
                'HTTP_REFERER' => 'http://evil.test/public/habits.php',
            ],
            [
                'habit_id' => 0,
                'csrf_token' => $token,
            ],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Hábito inválido.', $response->getFlash()['error_message'] ?? null);
    }

    private function validCreatePayload(): array
    {
        return [
            'title' => 'Drink Water',
            'description' => 'Hydrate',
            'category' => 'Outros',
            'time' => 'Tarde',
            'color' => '#4a74ff',
            'icon' => 'droplet',
            'frequency' => 'daily',
            'goal_type' => 'completion',
            'goal_value' => 1,
            'goal_unit' => '',
        ];
    }
}


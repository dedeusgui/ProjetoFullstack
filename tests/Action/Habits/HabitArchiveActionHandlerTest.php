<?php

declare(strict_types=1);

namespace Tests\Action\Habits;

use App\Actions\Habits\HabitArchiveActionHandler;
use Tests\Support\ActionTestCase;

final class HabitArchiveActionHandlerTest extends ActionTestCase
{
    public function testArchiveMarksHabitAsInactive(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, ['is_active' => 1, 'archived_at' => null]);
        $handler = new HabitArchiveActionHandler();
        $token = 'csrf-archive';

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['habit_id' => $habitId, 'operation' => 'archive', 'csrf_token' => $token],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Hábito arquivado com sucesso!', $response->getFlash()['success_message'] ?? null);

        $row = $this->db()->fetchOne('SELECT is_active, archived_at FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('0', (string) ($row['is_active'] ?? '1'));
        self::assertNotNull($row['archived_at'] ?? null);
    }

    public function testRestoreOperationRestoresHabit(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, [
            'is_active' => 0,
            'archived_at' => '2026-02-01 09:00:00',
        ]);
        $handler = new HabitArchiveActionHandler();
        $token = 'csrf-restore';

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['habit_id' => $habitId, 'operation' => 'restore', 'csrf_token' => $token],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Hábito restaurado com sucesso!', $response->getFlash()['success_message'] ?? null);

        $row = $this->db()->fetchOne('SELECT is_active, archived_at FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('1', (string) ($row['is_active'] ?? '0'));
        self::assertNull($row['archived_at']);
    }

    public function testUnknownOperationDefaultsToArchive(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, ['is_active' => 1, 'archived_at' => null]);
        $handler = new HabitArchiveActionHandler();
        $token = 'csrf-archive-default';

        $this->setRequest(
            ['REQUEST_METHOD' => 'POST'],
            ['habit_id' => $habitId, 'operation' => 'unknown', 'csrf_token' => $token],
            ['user_id' => $userId, 'csrf_token' => $token]
        );

        $response = $handler->handle($this->conn(), $_POST, $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/habits.php');
        self::assertSame('Hábito arquivado com sucesso!', $response->getFlash()['success_message'] ?? null);
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT is_active FROM habits WHERE id = ' . (int) $habitId)['is_active'] ?? '1'));
    }
}


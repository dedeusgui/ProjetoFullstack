<?php

declare(strict_types=1);

namespace Tests\Action\Repository;

use App\Repository\HabitRepository;
use Tests\Support\ActionTestCase;

final class HabitRepositoryTest extends ActionTestCase
{
    public function testUserOwnsHabitChecksOwnership(): void
    {
        $ownerId = $this->fixtures->createUser(['_counter' => 1]);
        $otherId = $this->fixtures->createUser(['_counter' => 2]);
        $habitId = $this->fixtures->createHabit($ownerId);
        $repository = new HabitRepository($this->conn());

        self::assertTrue($repository->userOwnsHabit($habitId, $ownerId));
        self::assertFalse($repository->userOwnsHabit($habitId, $otherId));
    }

    public function testArchiveRestoreAndDeleteMutateHabitState(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, ['is_active' => 1, 'archived_at' => null]);
        $repository = new HabitRepository($this->conn());

        self::assertTrue($repository->archiveForUser($habitId, $userId));
        $archived = $this->db()->fetchOne('SELECT is_active, archived_at FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('0', (string) ($archived['is_active'] ?? '1'));
        self::assertNotNull($archived['archived_at'] ?? null);

        self::assertTrue($repository->restoreForUser($habitId, $userId));
        $restored = $this->db()->fetchOne('SELECT is_active, archived_at FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('1', (string) ($restored['is_active'] ?? '0'));
        self::assertNull($restored['archived_at']);

        self::assertTrue($repository->deleteForUser($habitId, $userId));
        self::assertNull($this->db()->fetchOne('SELECT id FROM habits WHERE id = ' . (int) $habitId));
    }
}

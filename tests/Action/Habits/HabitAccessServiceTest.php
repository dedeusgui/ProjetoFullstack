<?php

declare(strict_types=1);

namespace Tests\Action\Habits;

use App\Habits\HabitAccessService;
use Tests\Support\ActionTestCase;

final class HabitAccessServiceTest extends ActionTestCase
{
    public function testUserOwnsHabitReturnsTrueForOwner(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId);
        $service = new HabitAccessService($this->conn());

        self::assertTrue($service->userOwnsHabit($habitId, $userId));
    }

    public function testUserOwnsHabitReturnsFalseForDifferentUserOrMissingHabit(): void
    {
        $ownerId = $this->fixtures->createUser(['_counter' => 1]);
        $otherId = $this->fixtures->createUser(['_counter' => 2]);
        $habitId = $this->fixtures->createHabit($ownerId);
        $service = new HabitAccessService($this->conn());

        self::assertFalse($service->userOwnsHabit($habitId, $otherId));
        self::assertFalse($service->userOwnsHabit(999999, $ownerId));
    }
}

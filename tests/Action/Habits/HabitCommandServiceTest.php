<?php

declare(strict_types=1);

namespace Tests\Action\Habits;

use App\Habits\HabitCommandService;
use Tests\Support\ActionTestCase;

final class HabitCommandServiceTest extends ActionTestCase
{
    public function testCreateReturnsErrorForUnknownCategory(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new HabitCommandService($this->conn());

        $result = $service->create($userId, $this->validPayload(['category' => 'Categoria Inexistente']));

        self::assertFalse($result['success'] ?? true);
        self::assertSame('Categoria inválida.', $result['message'] ?? null);
        self::assertSame('0', (string) ($this->db()->fetchOne('SELECT COUNT(*) AS c FROM habits')['c'] ?? '1'));
    }

    public function testCreateAppliesSanitizerAndPreparationDefaults(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new HabitCommandService($this->conn());

        $result = $service->create($userId, [
            'title' => '  Normalize Me  ',
            'description' => 'Desc',
            'category' => 'Outros',
            'time' => 'Qualquer',
            'color' => 'bad-color',
            'icon' => 'star',
            'frequency' => 'invalid',
            'goal_type' => 'invalid-goal',
            'goal_value' => 0,
            'goal_unit' => 'items',
            'target_days' => ['1', '9'],
        ]);

        self::assertTrue($result['success'] ?? false);
        $row = $this->db()->fetchOne('SELECT title, color, frequency, time_of_day, goal_type, goal_value, target_days, category_id FROM habits LIMIT 1');

        self::assertNotNull($row);
        self::assertSame('Normalize Me', $row['title'] ?? null);
        self::assertSame('#4a74ff', strtolower((string) ($row['color'] ?? '')));
        self::assertSame('daily', $row['frequency'] ?? null);
        self::assertSame('anytime', $row['time_of_day'] ?? null);
        self::assertSame('completion', $row['goal_type'] ?? null);
        self::assertSame('1', (string) ($row['goal_value'] ?? '0'));
        self::assertSame('[1]', $row['target_days'] ?? null);
        self::assertSame('10', (string) ($row['category_id'] ?? '0'));
    }

    public function testDeleteRejectsUnauthorizedUser(): void
    {
        $ownerId = $this->fixtures->createUser(['_counter' => 1]);
        $otherId = $this->fixtures->createUser(['_counter' => 2]);
        $habitId = $this->fixtures->createHabit($ownerId);
        $service = new HabitCommandService($this->conn());

        $result = $service->delete($otherId, $habitId);

        self::assertFalse($result['success'] ?? true);
        self::assertSame('Você não tem permissão para deletar este hábito.', $result['message'] ?? null);
        self::assertNotNull($this->db()->fetchOne('SELECT id FROM habits WHERE id = ' . (int) $habitId));
    }

    public function testDeleteRemovesHabitForOwner(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId);
        $service = new HabitCommandService($this->conn());

        $result = $service->delete($userId, $habitId);

        self::assertTrue($result['success'] ?? false);
        self::assertSame('Hábito deletado com sucesso!', $result['message'] ?? null);
        self::assertNull($this->db()->fetchOne('SELECT id FROM habits WHERE id = ' . (int) $habitId));
    }

    public function testArchiveAndRestoreUpdateActiveFlags(): void
    {
        $userId = $this->fixtures->createUser();
        $habitId = $this->fixtures->createHabit($userId, ['is_active' => 1, 'archived_at' => null]);
        $service = new HabitCommandService($this->conn());

        $archive = $service->archive($userId, $habitId);
        self::assertTrue($archive['success'] ?? false);
        self::assertSame('Hábito arquivado com sucesso!', $archive['message'] ?? null);

        $archived = $this->db()->fetchOne('SELECT is_active, archived_at FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('0', (string) ($archived['is_active'] ?? '1'));
        self::assertNotNull($archived['archived_at'] ?? null);

        $restore = $service->restore($userId, $habitId);
        self::assertTrue($restore['success'] ?? false);
        self::assertSame('Hábito restaurado com sucesso!', $restore['message'] ?? null);

        $restored = $this->db()->fetchOne('SELECT is_active, archived_at FROM habits WHERE id = ' . (int) $habitId);
        self::assertSame('1', (string) ($restored['is_active'] ?? '0'));
        self::assertArrayHasKey('archived_at', $restored);
        self::assertNull($restored['archived_at']);
    }

    public function testRestoreRejectsInvalidHabitId(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new HabitCommandService($this->conn());

        $result = $service->restore($userId, 0);

        self::assertFalse($result['success'] ?? true);
        self::assertSame('Hábito inválido.', $result['message'] ?? null);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }
}


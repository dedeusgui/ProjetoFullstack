<?php

declare(strict_types=1);

namespace Tests\Support;

final class FixtureLoader
{
    public function __construct(private readonly TestDatabase $db)
    {
    }

    public function createUser(array $overrides = []): int
    {
        $counter = (int) ($overrides['_counter'] ?? random_int(1000, 999999));
        unset($overrides['_counter']);

        $data = array_merge([
            'name' => 'Test User ' . $counter,
            'email' => 'test+' . $counter . '@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'timezone' => 'UTC',
            'is_active' => 1,
            'email_verified' => 1,
        ], $overrides);

        $stmt = $this->db->prepare('INSERT INTO users (name, email, password, timezone, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(
            'ssssii',
            $data['name'],
            $data['email'],
            $data['password'],
            $data['timezone'],
            $data['is_active'],
            $data['email_verified']
        );
        $stmt->execute();

        return (int) $stmt->insert_id;
    }

    public function createHabit(int $userId, array $overrides = []): int
    {
        $data = array_merge([
            'category_id' => 10,
            'title' => 'Read 10 pages',
            'description' => 'Reading habit',
            'icon' => 'book',
            'color' => '#4a74ff',
            'frequency' => 'daily',
            'target_days' => null,
            'time_of_day' => 'morning',
            'goal_type' => 'completion',
            'goal_value' => 1,
            'goal_unit' => null,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => 1,
            'archived_at' => null,
        ], $overrides);

        $stmt = $this->db->prepare('INSERT INTO habits (
            user_id, category_id, title, description, icon, color, frequency, target_days, time_of_day,
            goal_type, goal_value, goal_unit, start_date, end_date, is_active, archived_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $stmt->bind_param(
            'iissssssssisssis',
            $userId,
            $data['category_id'],
            $data['title'],
            $data['description'],
            $data['icon'],
            $data['color'],
            $data['frequency'],
            $data['target_days'],
            $data['time_of_day'],
            $data['goal_type'],
            $data['goal_value'],
            $data['goal_unit'],
            $data['start_date'],
            $data['end_date'],
            $data['is_active'],
            $data['archived_at']
        );
        $stmt->execute();

        return (int) $stmt->insert_id;
    }

    public function createCompletion(int $habitId, int $userId, array $overrides = []): int
    {
        $data = array_merge([
            'completion_date' => '2026-02-01',
            'value_achieved' => null,
            'notes' => null,
            'mood' => null,
        ], $overrides);

        $stmt = $this->db->prepare('INSERT INTO habit_completions (habit_id, user_id, completion_date, value_achieved, notes, mood) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(
            'iisdss',
            $habitId,
            $userId,
            $data['completion_date'],
            $data['value_achieved'],
            $data['notes'],
            $data['mood']
        );
        $stmt->execute();

        return (int) $stmt->insert_id;
    }
}

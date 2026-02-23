<?php

namespace App\Repository;

class UserRepository
{
    use InteractsWithDatabase;

    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findActiveByEmail(string $email): ?array
    {
        $stmt = $this->prepareOrFail('SELECT id, name, email, password FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('s', $email);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->fetch_assoc() ?: null;
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId !== null) {
            $stmt = $this->prepareOrFail('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->bind_param('si', $email, $excludeUserId);
            $this->executeOrFail($stmt);
            return $this->getResultOrFail($stmt)->num_rows > 0;
        }

        $stmt = $this->prepareOrFail('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $this->executeOrFail($stmt);

        return $this->getResultOrFail($stmt)->num_rows > 0;
    }

    public function createUser(string $name, string $email, string $passwordHash): ?int
    {
        $stmt = $this->prepareOrFail('INSERT INTO users (name, email, password, is_active, email_verified) VALUES (?, ?, ?, 1, 0)');
        $stmt->bind_param('sss', $name, $email, $passwordHash);

        $this->executeOrFail($stmt);

        return (int) $stmt->insert_id;
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->prepareOrFail('UPDATE users SET last_login = NOW() WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
    }

    public function findTimezoneById(int $userId): ?string
    {
        $stmt = $this->prepareOrFail('SELECT timezone FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc();

        return !empty($row['timezone']) ? (string) $row['timezone'] : null;
    }

    public function findPasswordHashById(int $userId): ?string
    {
        $stmt = $this->prepareOrFail('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $this->executeOrFail($stmt);
        $row = $this->getResultOrFail($stmt)->fetch_assoc();

        return $row['password'] ?? null;
    }

    public function updateProfileWithoutPassword(int $userId, string $email, string $avatarUrl): bool
    {
        $stmt = $this->prepareOrFail('UPDATE users SET email = ?, avatar_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->bind_param('ssi', $email, $avatarUrl, $userId);
        $this->executeOrFail($stmt);
        return true;
    }

    public function updateProfileWithPassword(int $userId, string $email, string $avatarUrl, string $passwordHash): bool
    {
        $stmt = $this->prepareOrFail('UPDATE users SET email = ?, avatar_url = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->bind_param('sssi', $email, $avatarUrl, $passwordHash, $userId);
        $this->executeOrFail($stmt);
        return true;
    }
}

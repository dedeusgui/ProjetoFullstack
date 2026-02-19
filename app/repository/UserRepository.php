<?php

class UserRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findActiveByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, name, email, password FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId !== null) {
            $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->bind_param('si', $email, $excludeUserId);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }

        $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function createUser(string $name, string $email, string $passwordHash): ?int
    {
        $stmt = $this->conn->prepare('INSERT INTO users (name, email, password, is_active, email_verified) VALUES (?, ?, ?, 1, 0)');
        $stmt->bind_param('sss', $name, $email, $passwordHash);

        if (!$stmt->execute()) {
            return null;
        }

        return (int) $stmt->insert_id;
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public function findPasswordHashById(int $userId): ?string
    {
        $stmt = $this->conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row['password'] ?? null;
    }

    public function updateProfileWithoutPassword(int $userId, string $email, string $avatarUrl): bool
    {
        $stmt = $this->conn->prepare('UPDATE users SET email = ?, avatar_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->bind_param('ssi', $email, $avatarUrl, $userId);
        return $stmt->execute();
    }

    public function updateProfileWithPassword(int $userId, string $email, string $avatarUrl, string $passwordHash): bool
    {
        $stmt = $this->conn->prepare('UPDATE users SET email = ?, avatar_url = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->bind_param('sssi', $email, $avatarUrl, $passwordHash, $userId);
        return $stmt->execute();
    }
}

<?php

class AuthService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function authenticate(string $email, string $password): ?array
    {
        $email = $this->normalizeEmail($email);
        $stmt = $this->conn->prepare('SELECT id, name, email, password FROM users WHERE email = ? AND is_active = 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $user = $result->fetch_assoc();
        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];
    }

    public function emailExists(string $email): bool
    {
        $email = $this->normalizeEmail($email);
        $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    public function register(string $name, string $email, string $password): ?array
    {
        $name = trim($name);
        $email = $this->normalizeEmail($email);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $this->conn->prepare('INSERT INTO users (name, email, password, is_active, email_verified) VALUES (?, ?, ?, 1, 0)');
        $insertStmt->bind_param('sss', $name, $email, $passwordHash);

        if (!$insertStmt->execute()) {
            return null;
        }

        return [
            'id' => (int) $insertStmt->insert_id,
            'name' => $name,
            'email' => $email
        ];
    }

    public function updateLastLogin(int $userId): void
    {
        $updateStmt = $this->conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $updateStmt->bind_param('i', $userId);
        $updateStmt->execute();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}

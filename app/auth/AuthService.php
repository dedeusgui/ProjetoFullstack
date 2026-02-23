<?php

namespace App\Auth;

use App\Repository\UserRepository;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(\mysqli $conn)
    {
        $this->userRepository = new UserRepository($conn);
    }

    public function authenticate(string $email, string $password): ?array
    {
        $email = $this->normalizeEmail($email);
        $user = $this->userRepository->findActiveByEmail($email);

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
        return $this->userRepository->emailExists($this->normalizeEmail($email));
    }

    public function register(string $name, string $email, string $password): ?array
    {
        $name = trim($name);
        $email = $this->normalizeEmail($email);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($passwordHash) || $passwordHash === '') {
            if (\function_exists('appLogMessage')) {
                \appLogMessage('Password hash generation failed', ['service' => 'AuthService::register']);
            }
            return null;
        }

        $userId = $this->userRepository->createUser($name, $email, $passwordHash);
        if ($userId === null) {
            return null;
        }

        return [
            'id' => $userId,
            'name' => $name,
            'email' => $email
        ];
    }

    public function updateLastLogin(int $userId): void
    {
        $this->userRepository->updateLastLogin($userId);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}

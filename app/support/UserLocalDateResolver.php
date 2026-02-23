<?php

namespace App\Support;

use App\Repository\UserRepository;

class UserLocalDateResolver
{
    private UserRepository $userRepository;

    /** @var array<int,string> */
    private array $cache = [];

    public function __construct(\mysqli $conn)
    {
        $this->userRepository = new UserRepository($conn);
    }

    public function getTodayDateForUser(int $userId): string
    {
        if (isset($this->cache[$userId])) {
            return $this->cache[$userId];
        }

        $timezone = $this->userRepository->findTimezoneById($userId) ?? 'America/Sao_Paulo';

        try {
            $now = new \DateTime('now', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            $now = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        }

        $this->cache[$userId] = $now->format('Y-m-d');
        return $this->cache[$userId];
    }
}

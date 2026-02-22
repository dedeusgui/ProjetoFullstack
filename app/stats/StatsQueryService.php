<?php

namespace App\Stats;

class StatsQueryService
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getUserTodayDate(int $userId): string
    {
        return getUserTodayDate($this->conn, $userId);
    }

    public function getTotalHabits(int $userId): int
    {
        return (int) getTotalHabits($this->conn, $userId);
    }

    public function getCompletedToday(int $userId, ?string $date = null): int
    {
        return (int) getCompletedToday($this->conn, $userId, $date);
    }

    public function getCompletionRate(int $userId): int
    {
        return (int) getCompletionRate($this->conn, $userId);
    }

    public function getCompletionTrend(int $userId, int $windowDays = 7): array
    {
        return getCompletionTrend($this->conn, $userId, $windowDays);
    }

    public function getCompletionWindowSummary(int $userId, int $days = 0): array
    {
        return getCompletionWindowSummary($this->conn, $userId, $days);
    }

    public function getCurrentStreak(int $userId): int
    {
        return (int) getCurrentStreak($this->conn, $userId);
    }

    public function getTodayHabits(int $userId, ?string $targetDate = null): array
    {
        return getTodayHabits($this->conn, $userId, $targetDate);
    }

    public function getActiveDays(int $userId): int
    {
        return (int) getActiveDays($this->conn, $userId);
    }

    public function getMonthlyData(int $userId, int $days = 30): array
    {
        return getMonthlyData($this->conn, $userId, $days);
    }

    public function getTotalCompletions(int $userId): int
    {
        return (int) getTotalCompletions($this->conn, $userId);
    }

    public function getBestStreak(int $userId): int
    {
        return (int) getBestStreak($this->conn, $userId);
    }

    public function getCategoryStats(int $userId): array
    {
        return getCategoryStats($this->conn, $userId);
    }

    public function getUserCreatedAt(int $userId): ?string
    {
        return getUserCreatedAt($this->conn, $userId);
    }

    public function getRecentHistory(int $userId, int $days = 10, ?string $userCreatedAt = null): array
    {
        return getRecentHistory($this->conn, $userId, $days, $userCreatedAt);
    }
}

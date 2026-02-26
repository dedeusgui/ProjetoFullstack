<?php

use App\Achievements\AchievementService;
use App\Habits\HabitSchedulePolicy;
use App\Repository\CategoryRepository;
use App\Support\DateFormatter;
use App\Support\TimeOfDayMapper;
use App\UserProgress\UserProgressService;

// Transitional compatibility helpers (utilities + achievement/progress wrappers only).

function mapTimeOfDay($timePT) {
    return TimeOfDayMapper::toDatabase((string) $timePT);
}

function mapTimeOfDayReverse($timeEN) {
    return TimeOfDayMapper::toDisplay((string) $timeEN);
}

function getAppToday(): string {
    return date('Y-m-d');
}

function normalizeTargetDays(?string $targetDays): array {
    return HabitSchedulePolicy::normalizeTargetDays($targetDays);
}

function getNextHabitDueDate(array $habit, ?string $fromDate = null): ?string {
    return HabitSchedulePolicy::getNextDueDate($habit, $fromDate, getAppToday());
}

function formatDateBr(?string $date): string {
    return DateFormatter::formatBr($date);
}

function isHabitScheduledForDate(array $habit, string $date): bool {
    return HabitSchedulePolicy::isScheduledForDate($habit, $date);
}

function getCategoryIdByName($conn, $categoryName) {
    $repository = new CategoryRepository($conn);
    return $repository->findIdByName((string) $categoryName);
}

function mapAchievementIconToBootstrap(string $icon): string {
    return AchievementService::mapIconToBootstrap($icon);
}

function getDailyCompletionsMap($conn, $userId, $days = 365) {
    $service = new AchievementService($conn);
    return $service->getDailyCompletionsMap((int) $userId, (int) $days);
}

function getPerfectDaysStreak($conn, $userId, $days = 365) {
    $service = new AchievementService($conn);
    return $service->getPerfectDaysStreak((int) $userId, (int) $days);
}

function getUserAchievements($conn, $userId) {
    $service = new AchievementService($conn);
    return $service->getUserAchievements((int) $userId);
}

function calculateLevelFromXp(int $totalXp): int {
    $xp = max(0, $totalXp);
    $level = 1;

    while (true) {
        $nextLevel = $level + 1;
        $required = 0;
        for ($i = 2; $i <= $nextLevel; $i++) {
            if ($i <= 10) {
                $required += 100;
            } elseif ($i <= 20) {
                $required += 150;
            } elseif ($i <= 40) {
                $required += 200;
            } else {
                $required += 250;
            }
        }

        if ($xp < $required) {
            return $level;
        }

        $level++;
        if ($level > 1000) {
            return $level;
        }
    }
}

function persistUserProgress($conn, int $userId, int $level, int $experiencePoints): void {
    $service = new UserProgressService($conn);
    $service->persistUserProgress($userId, $level, $experiencePoints);
}

function getUserProgressSummary($conn, int $userId, ?array $achievements = null): array {
    $service = new UserProgressService($conn);
    return $service->refreshUserProgressSummary($userId, $achievements);
}

<?php

use App\Achievements\AchievementService;
use App\Habits\HabitSchedulePolicy;
use App\Repository\CategoryRepository;
use App\Support\DateFormatter;
use App\Support\TimeOfDayMapper;
use App\UserProgress\UserProgressService;

// Funções helper para hábitos e estatísticas

// Mapear time_of_day do português para inglês
function mapTimeOfDay($timePT) {
    return TimeOfDayMapper::toDatabase((string) $timePT);
}

// Mapear time_of_day do inglês para português
function mapTimeOfDayReverse($timeEN) {
    return TimeOfDayMapper::toDisplay((string) $timeEN);
}


function getAppToday(): string {
    return date('Y-m-d');
}


function getUserTodayDate(mysqli $conn, int $userId): string {
    static $cache = [];
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $timezone = 'America/Sao_Paulo';

    $stmt = $conn->prepare("SELECT timezone FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!empty($row['timezone'])) {
            $timezone = $row['timezone'];
        }
    }

    try {
        $now = new DateTime('now', new DateTimeZone($timezone));
    } catch (Throwable $e) {
        $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    }

    $cache[$userId] = $now->format('Y-m-d');
    return $cache[$userId];
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

// Buscar ID da categoria pelo nome
function getCategoryIdByName($conn, $categoryName) {
    $repository = new CategoryRepository($conn);
    return $repository->findIdByName((string) $categoryName);
}

// Buscar todos os hábitos do usuário
function getUserHabits($conn, $userId) {
    $sql = "
        SELECT 
            h.*,
            c.name as category_name,
            c.color as category_color,
            EXISTS(
                SELECT 1 FROM habit_completions 
                WHERE habit_id = h.id 
                AND completion_date = ?
                AND user_id = h.user_id
            ) as completed_today
        FROM habits h
        LEFT JOIN categories c ON h.category_id = c.id
        WHERE h.user_id = ? AND h.is_active = 1 AND h.archived_at IS NULL
        ORDER BY h.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $today = getUserTodayDate($conn, (int) $userId);
    $stmt->bind_param("si", $today, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $habits = [];
    while ($row = $result->fetch_assoc()) {
        $habits[] = $row;
    }
    
    return $habits;
}

function getArchivedHabits($conn, $userId) {
    $sql = "
        SELECT 
            h.*,
            c.name as category_name,
            c.color as category_color,
            EXISTS(
                SELECT 1 FROM habit_completions 
                WHERE habit_id = h.id 
                AND completion_date = ?
                AND user_id = h.user_id
            ) as completed_today
        FROM habits h
        LEFT JOIN categories c ON h.category_id = c.id
        WHERE h.user_id = ? AND h.archived_at IS NOT NULL
        ORDER BY h.archived_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $today = getUserTodayDate($conn, (int) $userId);
    $stmt->bind_param("si", $today, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $habits = [];
    while ($row = $result->fetch_assoc()) {
        $habits[] = $row;
    }

    return $habits;
}

// Buscar hábitos de hoje
function getTodayHabits($conn, $userId, ?string $targetDate = null) {
    $sql = "
        SELECT 
            h.*,
            c.name as category_name,
            EXISTS(
                SELECT 1 FROM habit_completions 
                WHERE habit_id = h.id 
                AND completion_date = ?
                AND user_id = h.user_id
            ) as completed_today
        FROM habits h
        LEFT JOIN categories c ON h.category_id = c.id
        WHERE h.user_id = ? AND h.is_active = 1 AND h.archived_at IS NULL
        ORDER BY h.time_of_day, h.title
    ";
    
    $stmt = $conn->prepare($sql);
    $today = $targetDate ?? getUserTodayDate($conn, (int) $userId);
    $stmt->bind_param("si", $today, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $habits = [];
    while ($row = $result->fetch_assoc()) {
        if (isHabitScheduledForDate($row, $today)) {
            $habits[] = $row;
        }
    }
    
    return $habits;
}

// Total de hábitos ativos
function getTotalHabits($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM habits WHERE user_id = ? AND is_active = 1 AND archived_at IS NULL");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getArchivedHabitsCount($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM habits WHERE user_id = ? AND archived_at IS NOT NULL");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Hábitos concluídos hoje
function getCompletedToday($conn, $userId, ?string $date = null) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT hc.habit_id) as total
        FROM habit_completions hc
        INNER JOIN habits h ON hc.habit_id = h.id
        WHERE hc.user_id = ?
        AND hc.completion_date = ?
        AND h.is_active = 1
    ");
    $today = $date ?? getUserTodayDate($conn, (int) $userId);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Taxa de conclusão (histórico completo do usuário)
function getCompletionRate($conn, $userId) {
    $summary = getCompletionWindowSummary($conn, $userId);
    return (int) ($summary['rate'] ?? 0);
}

function getCompletionWindowSummary($conn, $userId, int $days = 0): array {
    $today = getUserTodayDate($conn, (int) $userId);
    $startDate = getCompletionWindowStartDate($conn, $userId, $days, $today);

    if ($startDate === null || $startDate > $today) {
        return ['rate' => 0, 'completed' => 0, 'scheduled' => 0, 'days_analyzed' => 0];
    }

    return getCompletionSummaryByRange($conn, $userId, $startDate, $today);
}

function getCompletionWindowStartDate($conn, $userId, int $days, string $referenceDate): ?string {
    $userCreatedAt = getUserCreatedAt($conn, $userId);
    if (empty($userCreatedAt)) {
        return null;
    }

    $createdDate = date('Y-m-d', strtotime($userCreatedAt));
    $maxDays = max(0, $days);

    if ($maxDays === 0) {
        // Usar a data da primeira conclusão para não penalizar dias
        // anteriores ao usuário começar a usar a plataforma
        $stmt = $conn->prepare("SELECT MIN(completion_date) FROM habit_completions WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $firstCompletion = $row[0] ?? null;
        if ($firstCompletion !== null && $firstCompletion > $createdDate) {
            return $firstCompletion;
        }
        return $createdDate;
    }

    $windowStart = date('Y-m-d', strtotime('-' . ($maxDays - 1) . ' days', strtotime($referenceDate)));
    return $windowStart > $createdDate ? $windowStart : $createdDate;
}

function getCompletionSummaryByRange($conn, $userId, string $startDate, string $endDate): array {
    if ($startDate > $endDate) {
        return ['rate' => 0, 'completed' => 0, 'scheduled' => 0, 'days_analyzed' => 0];
    }

    $daysAnalyzed = (int) max(0, (strtotime($endDate) - strtotime($startDate)) / 86400) + 1;

    // Busca hábitos com dados completos de frequência para calcular scheduled corretamente
    $habitStmt = $conn->prepare("
        SELECT id, frequency, target_days, start_date, end_date, created_at, archived_at
        FROM habits
        WHERE user_id = ?
          AND is_active = 1
          AND DATE(created_at) <= ?
          AND (archived_at IS NULL OR DATE(archived_at) > ?)
    ");
    $habitStmt->bind_param('iss', $userId, $endDate, $startDate);
    $habitStmt->execute();
    $habits = $habitStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $scheduled = 0;
    if (!empty($habits)) {
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            foreach ($habits as $habit) {
                $habitStart = max($startDate, date('Y-m-d', strtotime($habit['created_at'])));
                $archivedDate = !empty($habit['archived_at'])
                    ? date('Y-m-d', strtotime($habit['archived_at'] . ' -1 day'))
                    : $endDate;
                $habitEnd = min($endDate, $archivedDate);

                if ($dateStr >= $habitStart && $dateStr <= $habitEnd && isHabitScheduledForDate($habit, $dateStr)) {
                    $scheduled++;
                }
            }
            $current->modify('+1 day');
        }
    }

    $completedStmt = $conn->prepare("
        SELECT COUNT(DISTINCT CONCAT(hc.habit_id, '|', hc.completion_date)) AS completed
        FROM habit_completions hc
        INNER JOIN habits h ON h.id = hc.habit_id AND h.user_id = hc.user_id
        WHERE hc.user_id = ?
          AND hc.completion_date BETWEEN ? AND ?
          AND h.is_active = 1
          AND hc.completion_date >= DATE(h.created_at)
          AND (h.archived_at IS NULL OR hc.completion_date < DATE(h.archived_at))
    ");
    $completedStmt->bind_param('iss', $userId, $startDate, $endDate);
    $completedStmt->execute();
    $completedRow = $completedStmt->get_result()->fetch_assoc() ?: [];
    $completed = (int) ($completedRow['completed'] ?? 0);

    $rate = $scheduled > 0 ? round(($completed / $scheduled) * 100) : 0;

    return [
        'rate' => (int) $rate,
        'completed' => $completed,
        'scheduled' => $scheduled,
        'days_analyzed' => $daysAnalyzed
    ];
}

function getCompletionTrend($conn, $userId, int $windowDays = 7): array {
    $period = max(1, $windowDays);

    $today = getUserTodayDate($conn, (int) $userId);
    $currentEnd = $today;
    $currentStart = getCompletionWindowStartDate($conn, $userId, $period, $currentEnd);

    if ($currentStart === null) {
        return ['status' => 'insufficient', 'label' => 'Dados insuficientes', 'icon' => 'bi-dash', 'delta' => 0];
    }

    $current = getCompletionSummaryByRange($conn, $userId, $currentStart, $currentEnd);

    $previousEnd = date('Y-m-d', strtotime($currentStart . ' -1 day'));
    $previousStart = date('Y-m-d', strtotime('-' . ($period - 1) . ' days', strtotime($previousEnd)));

    $createdDate = getCompletionWindowStartDate($conn, $userId, 0, $today);
    if ($createdDate === null || $previousEnd < $createdDate) {
        return ['status' => 'insufficient', 'label' => 'Dados insuficientes', 'icon' => 'bi-dash', 'delta' => 0];
    }

    if ($previousStart < $createdDate) {
        $previousStart = $createdDate;
    }

    $previous = getCompletionSummaryByRange($conn, $userId, $previousStart, $previousEnd);

    if (($current['scheduled'] ?? 0) === 0 || ($previous['scheduled'] ?? 0) === 0) {
        return ['status' => 'insufficient', 'label' => 'Dados insuficientes', 'icon' => 'bi-dash', 'delta' => 0];
    }

    $delta = (int) ($current['rate'] - $previous['rate']);

    if ($delta > 0) {
        return ['status' => 'up', 'label' => '+' . $delta . '% vs semana anterior', 'icon' => 'bi-arrow-up', 'delta' => $delta];
    }

    if ($delta < 0) {
        return ['status' => 'down', 'label' => $delta . '% vs semana anterior', 'icon' => 'bi-arrow-down', 'delta' => $delta];
    }

    return ['status' => 'stable', 'label' => 'Sem alteração vs semana anterior', 'icon' => 'bi-dash', 'delta' => 0];
}


function getActiveDays($conn, $userId): int {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT completion_date) AS total
        FROM habit_completions
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}

// Sequência atual (streak)
function getCurrentStreak($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT DISTINCT completion_date 
        FROM habit_completions 
        WHERE user_id = ?
        ORDER BY completion_date DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['completion_date'];
    }
    
    if (empty($dates)) {
        return 0;
    }
    
    $streak = 0;
    $today = getUserTodayDate($conn, (int) $userId);
    $checkDate = new DateTime($today);
    
    foreach ($dates as $dateStr) {
        $completionDate = new DateTime($dateStr);
        $diff = $checkDate->diff($completionDate)->days;
        
        // Aceitar hoje (0) ou ontem (1)
        if ($diff <= 1) {
            $streak++;
            $checkDate = $completionDate;
        } else {
            break;
        }
    }
    
    return $streak;
}

// Melhor sequência
function getBestStreak($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT COALESCE(MAX(longest_streak), 0) as best_streak
        FROM habits
        WHERE user_id = ? AND is_active = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['best_streak'] ?? 0;
}

// Total de conclusões
function getTotalCompletions($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM habit_completions 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Dados do gráfico mensal
function getMonthlyData($conn, $userId, $days = 30) {
    $days = max(1, (int) $days);
    $today = getUserTodayDate($conn, (int) $userId);
    $startDate = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));

    $stmt = $conn->prepare("
        SELECT 
            DATE(completion_date) as date,
            COUNT(*) as completed
        FROM habit_completions
        WHERE user_id = ? 
        AND completion_date BETWEEN ? AND ?
        GROUP BY DATE(completion_date)
        ORDER BY date ASC
    ");
    $stmt->bind_param("iss", $userId, $startDate, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $completions = [];
    while ($row = $result->fetch_assoc()) {
        $completions[$row['date']] = $row['completed'];
    }
    
    // Preencher array com todos os dias
    $monthlyData = [
        'labels' => [],
        'completed' => [],
        'total' => []
    ];
    
    $totalHabits = getTotalHabits($conn, $userId);
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime($today . " -$i days"));
        $day = date('j', strtotime($date));
        
        $monthlyData['labels'][] = $day;
        $monthlyData['completed'][] = $completions[$date] ?? 0;
        $monthlyData['total'][] = $totalHabits;
    }
    
    return $monthlyData;
}

// Estatísticas por categoria
function getCategoryStats($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            c.name as category,
            COUNT(hc.id) as total,
            ROUND((COUNT(hc.id) * 100.0 / (
                SELECT COUNT(*) 
                FROM habit_completions 
                WHERE user_id = ?
            )), 1) as percentage
        FROM habits h
        INNER JOIN habit_completions hc ON h.id = hc.habit_id
        LEFT JOIN categories c ON h.category_id = c.id
        WHERE h.user_id = ?
        GROUP BY c.id, c.name
        ORDER BY total DESC
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
    
    return $stats;
}

// Histórico recente
function getUserCreatedAt($conn, $userId): ?string {
    $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['created_at'] ?? null;
}

// Histórico recente
function getRecentHistory($conn, $userId, $days = 10, ?string $userCreatedAt = null) {
    $maxDays = max(1, (int) $days);
    $today = getUserTodayDate($conn, (int) $userId);

    if (!empty($userCreatedAt)) {
        $createdDate = date('Y-m-d', strtotime($userCreatedAt));
        $diffSeconds = strtotime($today) - strtotime($createdDate);
        if ($diffSeconds >= 0) {
            $daysSinceCreation = (int) floor($diffSeconds / 86400) + 1;
            $maxDays = min($maxDays, max(1, $daysSinceCreation));
        }
    }

    $startDate = date('Y-m-d', strtotime($today . ' -' . ($maxDays - 1) . ' days'));
    $endDate = $today;

    $stmt = $conn->prepare("
        WITH RECURSIVE date_range AS (
            SELECT ? AS day
            UNION ALL
            SELECT DATE_ADD(day, INTERVAL 1 DAY)
            FROM date_range
            WHERE day < ?
        ),
        initial_base AS (
            SELECT COUNT(*) AS initial_total
            FROM habits
            WHERE user_id = ?
              AND DATE(created_at) < ?
        ),
        created_daily AS (
            SELECT DATE(created_at) AS day, COUNT(*) AS created_count
            FROM habits
            WHERE user_id = ?
              AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
        ),
        completed_daily AS (
            SELECT completion_date AS day, COUNT(*) AS completed_count
            FROM habit_completions
            WHERE user_id = ?
              AND completion_date BETWEEN ? AND ?
            GROUP BY completion_date
        )
        SELECT
            dr.day AS date,
            COALESCE(cd.completed_count, 0) AS completed,
            ib.initial_total + SUM(COALESCE(cr.created_count, 0)) OVER (ORDER BY dr.day) AS total,
            CASE
                WHEN (ib.initial_total + SUM(COALESCE(cr.created_count, 0)) OVER (ORDER BY dr.day)) > 0 THEN
                    ROUND(
                        (COALESCE(cd.completed_count, 0) * 100.0)
                        / (ib.initial_total + SUM(COALESCE(cr.created_count, 0)) OVER (ORDER BY dr.day)),
                        1
                    )
                ELSE 0
            END AS percentage
        FROM date_range dr
        CROSS JOIN initial_base ib
        LEFT JOIN created_daily cr ON cr.day = dr.day
        LEFT JOIN completed_daily cd ON cd.day = dr.day
        ORDER BY dr.day DESC
    ");
    $stmt->bind_param('ssisississ', $startDate, $endDate, $userId, $startDate, $userId, $startDate, $endDate, $userId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'date' => $row['date'],
            'completed' => (int) ($row['completed'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
            'percentage' => (float) ($row['percentage'] ?? 0)
        ];
    }

    return $history;
}


// Buscar todas as categorias
function getAllCategories($conn) {
    $result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Mapear ícone salvo no banco para classe Bootstrap Icons
function mapAchievementIconToBootstrap(string $icon): string {
    return AchievementService::mapIconToBootstrap($icon);
}

function getDailyCompletionsMap($conn, $userId, $days = 365) {
    $service = new AchievementService($conn);
    return $service->getDailyCompletionsMap((int) $userId, (int) $days);
}

// Maior sequência de dias com todos os hábitos ativos concluídos
function getPerfectDaysStreak($conn, $userId, $days = 365) {
    $service = new AchievementService($conn);
    return $service->getPerfectDaysStreak((int) $userId, (int) $days);
}

// Carregar e sincronizar conquistas do usuário com base na tabela achievements
function getUserAchievements($conn, $userId) {
    $service = new AchievementService($conn);
    return $service->syncUserAchievements((int) $userId);
}

function calculateLevelFromXp(int $totalXp): int {
    return max(1, (int) floor(sqrt(max(0, $totalXp) / 120)) + 1);
}

function persistUserProgress($conn, int $userId, int $level, int $experiencePoints): void {
    $service = new UserProgressService($conn);
    $service->persistUserProgress($userId, $level, $experiencePoints);
}

function getUserProgressSummary($conn, int $userId, ?array $achievements = null): array {
    $service = new UserProgressService($conn);
    return $service->refreshUserProgressSummary($userId, $achievements);
}

<?php
// Funções helper para hábitos e estatísticas

// Mapear time_of_day do português para inglês
function mapTimeOfDay($timePT) {
    $map = [
        'Manhã' => 'morning',
        'Tarde' => 'afternoon',
        'Noite' => 'evening'
    ];
    return $map[$timePT] ?? 'anytime';
}

// Mapear time_of_day do inglês para português
function mapTimeOfDayReverse($timeEN) {
    $map = [
        'morning' => 'Manhã',
        'afternoon' => 'Tarde',
        'evening' => 'Noite',
        'anytime' => 'Qualquer'
    ];
    return $map[$timeEN] ?? 'Qualquer';
}


function getAppToday(): string {
    return date('Y-m-d');
}


function getUserTodayDate(mysqli $conn, int $userId): string {
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

    return $now->format('Y-m-d');
}

function normalizeTargetDays(?string $targetDays): array {
    if (empty($targetDays)) {
        return [];
    }

    $decoded = json_decode($targetDays, true);
    if (!is_array($decoded)) {
        return [];
    }

    $days = array_values(array_unique(array_map('intval', $decoded)));
    return array_values(array_filter($days, static fn($day) => $day >= 0 && $day <= 6));
}


function getNextHabitDueDate(array $habit, ?string $fromDate = null): ?string {
    $baseDate = $fromDate ?? getAppToday();
    $date = DateTime::createFromFormat('Y-m-d', $baseDate);
    if (!$date) {
        return null;
    }

    for ($i = 0; $i < 366; $i++) {
        $candidate = $date->format('Y-m-d');
        if (isHabitScheduledForDate($habit, $candidate)) {
            return $candidate;
        }
        $date->modify('+1 day');
    }

    return null;
}

function formatDateBr(?string $date): string {
    if (empty($date)) {
        return 'Sem data';
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed) {
        return $date;
    }

    return $parsed->format('d/m/Y');
}

function isHabitScheduledForDate(array $habit, string $date): bool {
    $targetDate = DateTime::createFromFormat('Y-m-d', $date);
    if (!$targetDate) {
        return false;
    }

    if (!empty($habit['start_date']) && $date < $habit['start_date']) {
        return false;
    }

    if (!empty($habit['end_date']) && $date > $habit['end_date']) {
        return false;
    }

    $frequency = $habit['frequency'] ?? 'daily';
    if ($frequency === 'daily') {
        return true;
    }

    $phpWeekDay = (int) $targetDate->format('w');

    if ($frequency === 'weekly') {
        $days = normalizeTargetDays($habit['target_days'] ?? null);
        if (empty($days)) {
            return $phpWeekDay === (int) date('w', strtotime((string) ($habit['start_date'] ?? $date)));
        }
        return in_array($phpWeekDay, $days, true);
    }

    if ($frequency === 'custom') {
        $days = normalizeTargetDays($habit['target_days'] ?? null);
        if (empty($days)) {
            return false;
        }
        return in_array($phpWeekDay, $days, true);
    }

    return true;
}

// Buscar ID da categoria pelo nome
function getCategoryIdByName($conn, $categoryName) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['id'] ?? null;
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
    $today = getAppToday();
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
    $today = getAppToday();
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
    $today = $targetDate ?? getAppToday();
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
function getCompletedToday($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT hc.habit_id) as total
        FROM habit_completions hc
        INNER JOIN habits h ON hc.habit_id = h.id
        WHERE hc.user_id = ? 
        AND hc.completion_date = ?
        AND h.is_active = 1
    ");
    $today = getAppToday();
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
    $today = getAppToday();
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

    $scheduledStmt = $conn->prepare("
        SELECT COALESCE(SUM(
            GREATEST(
                0,
                DATEDIFF(
                    LEAST(?, COALESCE(DATE_SUB(h.archived_at, INTERVAL 1 DAY), ?)),
                    GREATEST(?, DATE(h.created_at))
                ) + 1
            )
        ), 0) AS scheduled
        FROM habits h
        WHERE h.user_id = ?
          AND h.is_active = 1
          AND DATE(h.created_at) <= ?
          AND (h.archived_at IS NULL OR DATE(h.archived_at) > ?)
    ");
    $scheduledStmt->bind_param('sssiss', $endDate, $endDate, $startDate, $userId, $endDate, $startDate);
    $scheduledStmt->execute();
    $scheduledRow = $scheduledStmt->get_result()->fetch_assoc() ?: [];
    $scheduled = (int) ($scheduledRow['scheduled'] ?? 0);

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

    $today = getAppToday();
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
    $checkDate = new DateTime();
    
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
    $stmt = $conn->prepare("
        SELECT 
            DATE(completion_date) as date,
            COUNT(*) as completed
        FROM habit_completions
        WHERE user_id = ? 
        AND completion_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(completion_date)
        ORDER BY date ASC
    ");
    $stmt->bind_param("ii", $userId, $days);
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
        $date = date('Y-m-d', strtotime("-$i days"));
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

    if (!empty($userCreatedAt)) {
        $createdDate = date('Y-m-d', strtotime($userCreatedAt));
        $todayDate = date('Y-m-d');
        $diffSeconds = strtotime($todayDate) - strtotime($createdDate);
        if ($diffSeconds >= 0) {
            $daysSinceCreation = (int) floor($diffSeconds / 86400) + 1;
            $maxDays = min($maxDays, max(1, $daysSinceCreation));
        }
    }

    $startDate = date('Y-m-d', strtotime('-' . ($maxDays - 1) . ' days'));
    $endDate = date('Y-m-d');

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
    $normalized = strtolower(trim($icon));

    $map = [
        'flag' => 'bi bi-flag-fill',
        'fire' => 'bi bi-fire',
        'trophy' => 'bi bi-trophy-fill',
        'star' => 'bi bi-star-fill',
        'award' => 'bi bi-award-fill',
        'collection' => 'bi bi-collection-fill',
        'rocket' => 'bi bi-rocket-takeoff-fill',
        'gem' => 'bi bi-gem',
        'patch-check' => 'bi bi-patch-check-fill',
        'check' => 'bi bi-check-circle-fill'
    ];

    if ($normalized === '') {
        return 'bi bi-patch-check-fill';
    }

    if (isset($map[$normalized])) {
        return $map[$normalized];
    }

    // Aceita valor já salvo como classe completa
    if (str_starts_with($normalized, 'bi bi-')) {
        return $normalized;
    }

    if (str_starts_with($normalized, 'bi-')) {
        return 'bi ' . $normalized;
    }

    return 'bi bi-patch-check-fill';
}


// Buscar total de hábitos concluídos por data
function getDailyCompletionsMap($conn, $userId, $days = 365) {
    $stmt = $conn->prepare("\n        SELECT completion_date, COUNT(DISTINCT habit_id) as completed\n        FROM habit_completions\n        WHERE user_id = ?\n          AND completion_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)\n        GROUP BY completion_date\n    ");
    $stmt->bind_param("ii", $userId, $days);
    $stmt->execute();
    $result = $stmt->get_result();

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $map[$row['completion_date']] = (int) $row['completed'];
    }

    return $map;
}

// Maior sequência de dias com todos os hábitos ativos concluídos
function getPerfectDaysStreak($conn, $userId, $days = 365) {
    $totalHabits = getTotalHabits($conn, $userId);
    if ($totalHabits <= 0) {
        return 0;
    }

    $dailyMap = getDailyCompletionsMap($conn, $userId, $days);

    $maxStreak = 0;
    $currentStreak = 0;

    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $completed = $dailyMap[$date] ?? 0;

        if ($completed >= $totalHabits) {
            $currentStreak++;
            $maxStreak = max($maxStreak, $currentStreak);
        } else {
            $currentStreak = 0;
        }
    }

    return $maxStreak;
}

// Carregar e sincronizar conquistas do usuário com base na tabela achievements
function getUserAchievements($conn, $userId) {
    $totalHabits = getTotalHabits($conn, $userId);
    $totalCompletions = getTotalCompletions($conn, $userId);
    $bestStreak = getBestStreak($conn, $userId);

    $perfectStreak = getPerfectDaysStreak($conn, $userId, 730);

    $metrics = [
        'streak' => $bestStreak,
        'total_completions' => $totalCompletions,
        'habits_count' => $totalHabits,
        'perfect_week' => $perfectStreak,
        'perfect_month' => $perfectStreak
    ];

    $categoryByCriteria = [
        'streak' => 'consistencia',
        'perfect_week' => 'consistencia',
        'perfect_month' => 'consistencia',
        'habits_count' => 'exploracao',
        'total_completions' => 'performance'
    ];

    $tierByRarity = [
        'common' => 'bronze',
        'rare' => 'prata',
        'epic' => 'ouro',
        'legendary' => 'ouro'
    ];


    // Conquistas já desbloqueadas
    $unlockedMap = [];
    $unlockedStmt = $conn->prepare("\n        SELECT achievement_id, unlocked_at\n        FROM user_achievements\n        WHERE user_id = ?\n    ");
    $unlockedStmt->bind_param("i", $userId);
    $unlockedStmt->execute();
    $unlockedResult = $unlockedStmt->get_result();
    while ($row = $unlockedResult->fetch_assoc()) {
        $unlockedMap[(int) $row['achievement_id']] = $row['unlocked_at'];
    }

    // Todas as conquistas ativas
    $achievementsStmt = $conn->prepare("\n        SELECT id, slug, name, description, icon, badge_color, criteria_type, criteria_value, points, rarity\n        FROM achievements\n        WHERE is_active = 1\n        ORDER BY criteria_value ASC, id ASC\n    ");
    $achievementsStmt->execute();
    $achievementsResult = $achievementsStmt->get_result();

    $achievements = [];

    $justUnlockedIds = [];

    while ($achievement = $achievementsResult->fetch_assoc()) {
        $achievementId = (int) $achievement['id'];
        $slug = $achievement['slug'];
        $criteriaType = $achievement['criteria_type'];
        $criteriaValue = (int) $achievement['criteria_value'];
        $criteriaValue = $criteriaValue > 0 ? $criteriaValue : 1;

        $metricValue = (int) ($metrics[$criteriaType] ?? 0);

        if ($criteriaType === 'perfect_week') {
            $targetDays = 7 * $criteriaValue;
            $progress = min(100, round(($metricValue / $targetDays) * 100));
            $isUnlocked = $metricValue >= $targetDays;
        } elseif ($criteriaType === 'perfect_month') {
            $targetDays = 30 * $criteriaValue;
            $progress = min(100, round(($metricValue / $targetDays) * 100));
            $isUnlocked = $metricValue >= $targetDays;
        } else {
            $progress = min(100, round(($metricValue / $criteriaValue) * 100));
            $isUnlocked = $metricValue >= $criteriaValue;
        }

        // Sincronizar desbloqueio em user_achievements
        if ($isUnlocked && !isset($unlockedMap[$achievementId])) {
            $insertStmt = $conn->prepare("\n                INSERT INTO user_achievements (user_id, achievement_id, progress)\n                VALUES (?, ?, ?)\n            ");
            $insertStmt->bind_param("iii", $userId, $achievementId, $progress);
            $insertStmt->execute();

            $unlockedMap[$achievementId] = date('Y-m-d H:i:s');
            $justUnlockedIds[$achievementId] = true;
        }

        $currentValue = 0;
        $targetValue = $criteriaValue;
        $progressLabel = '';

        if ($criteriaType === 'perfect_week') {
            $targetValue = 7 * $criteriaValue;
            $currentValue = $metricValue;
            $progressLabel = $currentValue . '/' . $targetValue . ' dias perfeitos';
        } elseif ($criteriaType === 'perfect_month') {
            $targetValue = 30 * $criteriaValue;
            $currentValue = $metricValue;
            $progressLabel = $currentValue . '/' . $targetValue . ' dias perfeitos';
        } else {
            $currentValue = min($metricValue, $criteriaValue);
            $progressLabel = $currentValue . '/' . $criteriaValue;
        }

        $meta = [
            'category' => $categoryByCriteria[$criteriaType] ?? 'performance',
            'tier' => $tierByRarity[$achievement['rarity']] ?? 'bronze'
        ];

        $achievements[] = [
            'id' => $achievementId,
            'slug' => $slug,
            'name' => $achievement['name'],
            'description' => $achievement['description'],
            'icon' => mapAchievementIconToBootstrap($achievement['icon'] ?? ''),
            'badge_color' => $achievement['badge_color'] ?? '#4a74ff',
            'criteria_type' => $criteriaType,
            'criteria_value' => $criteriaValue,
            'points' => (int) $achievement['points'],
            'rarity' => $achievement['rarity'],
            'progress' => $progress,
            'progress_percent' => $progress,
            'progress_current' => $currentValue,
            'progress_target' => $targetValue,
            'progress_label' => $progressLabel,
            'is_near_completion' => !$isUnlocked && $progress >= 80,
            'category' => $meta['category'],
            'tier' => $meta['tier'],
            'unlocked' => isset($unlockedMap[$achievementId]) || $isUnlocked,
            'just_unlocked' => isset($justUnlockedIds[$achievementId]),
            'date' => $unlockedMap[$achievementId] ?? null
        ];
    }

    return $achievements;
}

function calculateLevelFromXp(int $totalXp): int {
    return max(1, (int) floor(sqrt(max(0, $totalXp) / 120)) + 1);
}

function persistUserProgress($conn, int $userId, int $level, int $experiencePoints): void {
    static $hasLevelColumn = null;
    static $hasXpColumn = null;

    if ($hasLevelColumn === null) {
        $levelCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'level'");
        $xpCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'experience_points'");
        $hasLevelColumn = $levelCheck && $levelCheck->num_rows > 0;
        $hasXpColumn = $xpCheck && $xpCheck->num_rows > 0;
    }

    if (!$hasLevelColumn && !$hasXpColumn) {
        return;
    }

    if ($hasLevelColumn && $hasXpColumn) {
        $stmt = $conn->prepare("\n            UPDATE users\n            SET level = ?, experience_points = ?\n            WHERE id = ?\n        ");
        $stmt->bind_param('iii', $level, $experiencePoints, $userId);
        $stmt->execute();
        return;
    }

    if ($hasLevelColumn) {
        $stmt = $conn->prepare("UPDATE users SET level = ? WHERE id = ?");
        $stmt->bind_param('ii', $level, $userId);
        $stmt->execute();
        return;
    }

    $stmt = $conn->prepare("UPDATE users SET experience_points = ? WHERE id = ?");
    $stmt->bind_param('ii', $experiencePoints, $userId);
    $stmt->execute();
}

function getUserProgressSummary($conn, int $userId, ?array $achievements = null): array {
    $achievementList = $achievements ?? getUserAchievements($conn, $userId);
    $unlockedAchievements = array_values(array_filter($achievementList, static function (array $achievement): bool {
        return !empty($achievement['unlocked']);
    }));

    $totalXp = array_sum(array_map(static function (array $achievement): int {
        return (int) ($achievement['points'] ?? 0);
    }, $unlockedAchievements));

    $currentLevel = calculateLevelFromXp($totalXp);
    $xpLevelStart = (($currentLevel - 1) ** 2) * 120;
    $xpLevelEnd = ($currentLevel ** 2) * 120;
    $xpIntoCurrentLevel = max(0, $totalXp - $xpLevelStart);
    $xpNeededForLevel = max(1, $xpLevelEnd - $xpLevelStart);

    persistUserProgress($conn, $userId, $currentLevel, $totalXp);

    return [
        'level' => $currentLevel,
        'total_xp' => $totalXp,
        'xp_into_level' => $xpIntoCurrentLevel,
        'xp_needed_for_level' => $xpNeededForLevel,
        'xp_to_next_level' => max(0, $xpLevelEnd - $totalXp),
        'xp_progress_percent' => min(100, (int) round(($xpIntoCurrentLevel / $xpNeededForLevel) * 100)),
        'unlocked_achievements' => $unlockedAchievements,
        'unlocked_achievements_count' => count($unlockedAchievements),
        'achievements_count' => count($achievementList)
    ];
}

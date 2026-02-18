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

// Taxa de conclusão (últimos 30 dias)
function getCompletionRate($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT hc.completion_date) as days_active,
            (SELECT COUNT(*) FROM habits WHERE user_id = ? AND is_active = 1) as total_habits
        FROM habit_completions hc
        INNER JOIN habits h ON hc.habit_id = h.id
        WHERE hc.user_id = ? 
        AND hc.completion_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND h.is_active = 1
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $daysActive = $row['days_active'] ?? 0;
    $totalHabits = $row['total_habits'] ?? 0;
    
    if ($totalHabits == 0) {
        return 0;
    }
    
    // Taxa = (dias ativos / 30) * 100
    return round(($daysActive / 30) * 100);
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
    $history = [];
    $totalHabits = getTotalHabits($conn, $userId);

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
    
    for ($i = 0; $i < $maxDays; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as completed
            FROM habit_completions 
            WHERE user_id = ? AND completion_date = ?
        ");
        $stmt->bind_param("is", $userId, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $completed = $row['completed'] ?? 0;
        
        $percentage = $totalHabits > 0 ? round(($completed / $totalHabits) * 100, 1) : 0;
        
        $history[] = [
            'date' => $date,
            'completed' => $completed,
            'total' => $totalHabits,
            'percentage' => $percentage
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
    $map = [
        'flag' => 'bi-flag',
        'fire' => 'bi-fire',
        'trophy' => 'bi-trophy',
        'star' => 'bi-star',
        'award' => 'bi-award',
        'collection' => 'bi-collection',
        'rocket' => 'bi-rocket',
        'gem' => 'bi-gem'
    ];

    return $map[$icon] ?? 'bi-patch-check';
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

// Catálogo padrão e sincronização de conquistas

function syncDefaultAchievementsCatalog($conn): void {
    $catalog = [
        ['slug' => 'first-step', 'name' => 'Primeiro Passo', 'description' => 'Complete seu primeiro hábito', 'icon' => 'flag', 'badge_color' => '#59d186', 'criteria_type' => 'total_completions', 'criteria_value' => 1, 'points' => 10, 'rarity' => 'common'],
        ['slug' => 'daily-rhythm-3', 'name' => 'Ritmo Inicial', 'description' => 'Mantenha um streak de 3 dias', 'icon' => 'fire', 'badge_color' => '#ff9500', 'criteria_type' => 'streak', 'criteria_value' => 3, 'points' => 20, 'rarity' => 'common'],
        ['slug' => 'week-warrior', 'name' => 'Guerreiro Semanal', 'description' => 'Mantenha um streak de 7 dias', 'icon' => 'fire', 'badge_color' => '#ff9500', 'criteria_type' => 'streak', 'criteria_value' => 7, 'points' => 50, 'rarity' => 'rare'],
        ['slug' => 'daily-rhythm-14', 'name' => 'Foco de 2 Semanas', 'description' => 'Mantenha um streak de 14 dias', 'icon' => 'trophy', 'badge_color' => '#FFD700', 'criteria_type' => 'streak', 'criteria_value' => 14, 'points' => 90, 'rarity' => 'rare'],
        ['slug' => 'month-master', 'name' => 'Mestre do Mês', 'description' => 'Mantenha um streak de 30 dias', 'icon' => 'trophy', 'badge_color' => '#FFD700', 'criteria_type' => 'streak', 'criteria_value' => 30, 'points' => 200, 'rarity' => 'epic'],
        ['slug' => 'daily-rhythm-60', 'name' => 'Lenda da Rotina', 'description' => 'Mantenha um streak de 60 dias', 'icon' => 'rocket', 'badge_color' => '#ff5757', 'criteria_type' => 'streak', 'criteria_value' => 60, 'points' => 350, 'rarity' => 'legendary'],
        ['slug' => 'focus-10', 'name' => 'Meta 10', 'description' => 'Complete 10 hábitos', 'icon' => 'star', 'badge_color' => '#9b59b6', 'criteria_type' => 'total_completions', 'criteria_value' => 10, 'points' => 30, 'rarity' => 'common'],
        ['slug' => 'century-club', 'name' => 'Clube dos 100', 'description' => 'Complete 100 hábitos', 'icon' => 'star', 'badge_color' => '#9b59b6', 'criteria_type' => 'total_completions', 'criteria_value' => 100, 'points' => 150, 'rarity' => 'rare'],
        ['slug' => 'focus-250', 'name' => 'Maratonista 250', 'description' => 'Complete 250 hábitos', 'icon' => 'award', 'badge_color' => '#4a74ff', 'criteria_type' => 'total_completions', 'criteria_value' => 250, 'points' => 280, 'rarity' => 'epic'],
        ['slug' => 'focus-500', 'name' => 'Elite 500', 'description' => 'Complete 500 hábitos', 'icon' => 'gem', 'badge_color' => '#3498db', 'criteria_type' => 'total_completions', 'criteria_value' => 500, 'points' => 500, 'rarity' => 'legendary'],
        ['slug' => 'dedication', 'name' => 'Dedicação Total', 'description' => 'Complete 1000 hábitos', 'icon' => 'gem', 'badge_color' => '#3498db', 'criteria_type' => 'total_completions', 'criteria_value' => 1000, 'points' => 1000, 'rarity' => 'legendary'],
        ['slug' => 'builder-3', 'name' => 'Planejador', 'description' => 'Crie 3 hábitos diferentes', 'icon' => 'collection', 'badge_color' => '#e67e22', 'criteria_type' => 'habits_count', 'criteria_value' => 3, 'points' => 25, 'rarity' => 'common'],
        ['slug' => 'habit-collector', 'name' => 'Colecionador de Hábitos', 'description' => 'Crie 10 hábitos diferentes', 'icon' => 'collection', 'badge_color' => '#e67e22', 'criteria_type' => 'habits_count', 'criteria_value' => 10, 'points' => 75, 'rarity' => 'rare'],
        ['slug' => 'builder-20', 'name' => 'Arquiteto de Rotina', 'description' => 'Crie 20 hábitos diferentes', 'icon' => 'collection', 'badge_color' => '#e67e22', 'criteria_type' => 'habits_count', 'criteria_value' => 20, 'points' => 180, 'rarity' => 'epic'],
        ['slug' => 'perfect-week', 'name' => 'Semana Perfeita', 'description' => 'Complete todos os hábitos por 7 dias seguidos', 'icon' => 'award', 'badge_color' => '#4a74ff', 'criteria_type' => 'perfect_week', 'criteria_value' => 1, 'points' => 100, 'rarity' => 'epic'],
        ['slug' => 'perfect-2-weeks', 'name' => '14 Dias Sem Falhar', 'description' => 'Complete todos os hábitos por 14 dias seguidos', 'icon' => 'award', 'badge_color' => '#4a74ff', 'criteria_type' => 'perfect_week', 'criteria_value' => 2, 'points' => 230, 'rarity' => 'epic'],
        ['slug' => 'perfect-month', 'name' => 'Mês Perfeito', 'description' => 'Complete todos os hábitos por 30 dias seguidos', 'icon' => 'award', 'badge_color' => '#4a74ff', 'criteria_type' => 'perfect_month', 'criteria_value' => 1, 'points' => 420, 'rarity' => 'legendary'],
        ['slug' => 'unstoppable', 'name' => 'Imparável', 'description' => 'Mantenha um streak de 100 dias', 'icon' => 'rocket', 'badge_color' => '#ff5757', 'criteria_type' => 'streak', 'criteria_value' => 100, 'points' => 500, 'rarity' => 'legendary']
    ];

    $stmt = $conn->prepare("
        INSERT INTO achievements (slug, name, description, icon, badge_color, criteria_type, criteria_value, points, rarity, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            icon = VALUES(icon),
            badge_color = VALUES(badge_color),
            criteria_type = VALUES(criteria_type),
            criteria_value = VALUES(criteria_value),
            points = VALUES(points),
            rarity = VALUES(rarity),
            is_active = VALUES(is_active)
    ");

    if (!$stmt) {
        return;
    }

    foreach ($catalog as $item) {
        $stmt->bind_param(
            'ssssssiis',
            $item['slug'],
            $item['name'],
            $item['description'],
            $item['icon'],
            $item['badge_color'],
            $item['criteria_type'],
            $item['criteria_value'],
            $item['points'],
            $item['rarity']
        );
        $stmt->execute();
    }
}

// Carregar e sincronizar conquistas do usuário com base na tabela achievements
function getUserAchievements($conn, $userId) {
    syncDefaultAchievementsCatalog($conn);
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

    $achievementMeta = [
        'first-step' => ['category' => 'consistencia', 'tier' => 'bronze'],
        'week-warrior' => ['category' => 'consistencia', 'tier' => 'prata'],
        'month-master' => ['category' => 'consistencia', 'tier' => 'ouro'],
        'century-club' => ['category' => 'performance', 'tier' => 'prata'],
        'perfect-week' => ['category' => 'consistencia', 'tier' => 'ouro'],
        'habit-collector' => ['category' => 'exploracao', 'tier' => 'prata'],
        'unstoppable' => ['category' => 'consistencia', 'tier' => 'ouro'],
        'dedication' => ['category' => 'performance', 'tier' => 'ouro'],
        'daily-rhythm-3' => ['category' => 'consistencia', 'tier' => 'bronze'],
        'daily-rhythm-14' => ['category' => 'consistencia', 'tier' => 'prata'],
        'daily-rhythm-60' => ['category' => 'consistencia', 'tier' => 'ouro'],
        'focus-10' => ['category' => 'performance', 'tier' => 'bronze'],
        'focus-250' => ['category' => 'performance', 'tier' => 'prata'],
        'focus-500' => ['category' => 'performance', 'tier' => 'ouro'],
        'builder-3' => ['category' => 'exploracao', 'tier' => 'bronze'],
        'builder-20' => ['category' => 'exploracao', 'tier' => 'ouro'],
        'perfect-2-weeks' => ['category' => 'consistencia', 'tier' => 'prata'],
        'perfect-month' => ['category' => 'consistencia', 'tier' => 'ouro']
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

        $meta = $achievementMeta[$slug] ?? ['category' => 'performance', 'tier' => 'bronze'];

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

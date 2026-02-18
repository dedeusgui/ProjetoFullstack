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
function getTodayHabits($conn, $userId) {
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
    $today = getAppToday();
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
function getRecentHistory($conn, $userId, $days = 10) {
    $history = [];
    $totalHabits = getTotalHabits($conn, $userId);
    
    for ($i = 0; $i < $days; $i++) {
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

    while ($achievement = $achievementsResult->fetch_assoc()) {
        $achievementId = (int) $achievement['id'];
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
        }

        $achievements[] = [
            'id' => $achievementId,
            'slug' => $achievement['slug'],
            'name' => $achievement['name'],
            'description' => $achievement['description'],
            'icon' => mapAchievementIconToBootstrap($achievement['icon'] ?? ''),
            'badge_color' => $achievement['badge_color'] ?? '#4a74ff',
            'criteria_type' => $criteriaType,
            'criteria_value' => $criteriaValue,
            'points' => (int) $achievement['points'],
            'rarity' => $achievement['rarity'],
            'progress' => $progress,
            'unlocked' => isset($unlockedMap[$achievementId]) || $isUnlocked,
            'date' => $unlockedMap[$achievementId] ?? null
        ];
    }

    return $achievements;
}

<?php 
// Proteger página - requer login
require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';

requireLogin();

// Configurações da página
$showRegisterButton = false;
$hideLoginButton = true;

// Buscar dados do usuário logado
$userId = getUserId();
$userData = getCurrentUser($conn);

// Se não encontrou usuário, fazer logout
if (!$userData) {
    logout();
}

// Adicionar iniciais ao userData
$userData['initials'] = getInitials($userData['name']);

// Estatísticas de hábitos
$stats = [
    'total_habits' => getTotalHabits($conn, $userId),
    'active_habits' => getTotalHabits($conn, $userId),
    'archived_habits' => getArchivedHabitsCount($conn, $userId)
];

// Buscar hábitos ativos do usuário, hábitos de hoje e arquivados
$allActiveHabitsRaw = getUserHabits($conn, $userId);
$habitsRaw = getTodayHabits($conn, $userId);
$archivedHabitsRaw = getArchivedHabits($conn, $userId);

// Mapear para formato esperado pelo frontend
$habits = [];
foreach ($habitsRaw as $habit) {
    $habits[] = [
        'id' => $habit['id'],
        'name' => $habit['title'],
        'description' => $habit['description'] ?? '',
        'category' => $habit['category_name'] ?? 'Sem categoria',
        'time' => mapTimeOfDayReverse($habit['time_of_day']),
        'color' => $habit['color'] ?? '#4a74ff',
        'streak' => $habit['current_streak'],
        'completed_today' => (bool)$habit['completed_today'],
        'created_at' => $habit['created_at'],
        'frequency' => $habit['frequency'] ?? 'daily',
        'target_days' => normalizeTargetDays($habit['target_days'] ?? null),
        'goal_type' => $habit['goal_type'] ?? 'completion',
        'goal_value' => (int)($habit['goal_value'] ?? 1),
        'goal_unit' => $habit['goal_unit'] ?? '',
        'can_complete_today' => isHabitScheduledForDate($habit, getAppToday()) && !(bool)$habit['completed_today'],
        'next_due_date' => getNextHabitDueDate($habit, (bool)$habit['completed_today'] ? date('Y-m-d', strtotime(getAppToday().' +1 day')) : getAppToday())
    ];
}

$archivedHabits = [];
foreach ($archivedHabitsRaw as $habit) {
    $archivedHabits[] = [
        'id' => $habit['id'],
        'name' => $habit['title'],
        'category' => $habit['category_name'] ?? 'Sem categoria',
        'archived_at' => $habit['archived_at']
    ];
}


$weekDaysMeta = [
    1 => 'Segunda',
    2 => 'Terça',
    3 => 'Quarta',
    4 => 'Quinta',
    5 => 'Sexta',
    6 => 'Sábado',
    0 => 'Domingo'
];

$habitsByWeekDay = [];
foreach ($weekDaysMeta as $weekDayIndex => $weekDayLabel) {
    $habitsByWeekDay[$weekDayIndex] = [
        'label' => $weekDayLabel,
        'habits' => []
    ];
}

foreach ($allActiveHabitsRaw as $habitRaw) {
    $habit = [
        'id' => $habitRaw['id'],
        'name' => $habitRaw['title'],
        'frequency' => $habitRaw['frequency'] ?? 'daily',
        'target_days' => normalizeTargetDays($habitRaw['target_days'] ?? null)
    ];
    $frequency = $habit['frequency'] ?? 'daily';
    if ($frequency === 'daily') {
        foreach (array_keys($habitsByWeekDay) as $weekDayIndex) {
            $habitsByWeekDay[$weekDayIndex]['habits'][] = $habit;
        }
        continue;
    }

    $targetDays = $habit['target_days'] ?? [];
    foreach ($targetDays as $weekDayIndex) {
        if (isset($habitsByWeekDay[$weekDayIndex])) {
            $habitsByWeekDay[$weekDayIndex]['habits'][] = $habit;
        }
    }
}

// Buscar todas as categorias para o modal
$categories = getAllCategories($conn);

include_once "includes/header.php";
?>

<!-- Link para CSS do Dashboard -->
<link rel="stylesheet" href="assets/css/dashboard.css" />

<!-- Dashboard Wrapper -->
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <!-- User Info -->
        <div class="sidebar-header">
            <div class="sidebar-user">
                <div class="user-avatar">
                    <?php if (!empty($userData['avatar_url'])): ?>
                        <img src="<?php echo htmlspecialchars($userData['avatar_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar de <?php echo htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
                    <?php else: ?>
                        <?php echo htmlspecialchars($userData['initials'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h4 class="user-name"><?php echo $userData['name']; ?></h4>
                    <p class="user-email"><?php echo $userData['email']; ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h5 class="nav-section-title">Menu Principal</h5>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="bi bi-house-door"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="habits.php" class="nav-link active">
                            <i class="bi bi-list-check"></i>
                            <span>Meus Hábitos</span>
                            <span class="nav-badge"><?php echo $stats['active_habits']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="history.php" class="nav-link">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Histórico</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h5 class="nav-section-title">Conta</h5>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-open-settings-modal aria-controls="settingsModalOverlay" aria-haspopup="dialog">
                            <i class="bi bi-gear"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../actions/logout_action.php" class="nav-link">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sair</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <!-- Mensagens de Sucesso/Erro -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-success-theme" style="margin-bottom: var(--space-lg); padding: var(--space-md); border-radius: var(--radius-medium);">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-danger-theme" style="margin-bottom: var(--space-lg); padding: var(--space-md); border-radius: var(--radius-medium);">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="dashboard-header" style="margin-bottom: var(--space-lg);">
            <div class="d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: var(--space-md);">
                <div>
                    <h1 class="dashboard-title">Meus Hábitos 📝</h1>
                    <p class="dashboard-subtitle">Você está vendo apenas os hábitos programados para hoje.</p>
                </div>
                <button class="doitly-btn" onclick="openHabitModal('create')">
                    <i class="bi bi-plus-circle"></i>
                    Novo Hábito
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats" style="margin-bottom: var(--space-xl);">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total de Hábitos</span>
                    <div class="stat-icon">
                        <i class="bi bi-list-ul"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['total_habits']; ?></h2>
                <div class="stat-change neutral">
                    <i class="bi bi-dash"></i>
                    <span>Ativos agora</span>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-header">
                    <span class="stat-label">Concluídos Hoje</span>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo count(array_filter($habits, fn($h) => $h['completed_today'])); ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>de <?php echo count($habits); ?> devidos hoje</span>
                </div>
            </div>


            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Arquivados</span>
                    <div class="stat-icon">
                        <i class="bi bi-archive"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['archived_habits']; ?></h2>
                <div class="stat-change neutral">
                    <i class="bi bi-clock-history"></i>
                    <span>histórico preservado</span>
                </div>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-header">
                    <span class="stat-label">Maior Sequência</span>
                    <div class="stat-icon">
                        <i class="bi bi-fire"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo count($habits) > 0 ? max(array_column($habits, 'streak')) : 0; ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>dias consecutivos</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="dashboard-card" style="margin-bottom: var(--space-lg);">
            <div class="card-body">
                <div class="d-flex gap-md align-items-center" style="flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input 
                            type="text" 
                            class="doitly-input" 
                            placeholder="🔍 Buscar hábitos..."
                            id="searchInput"
                            onkeyup="filterHabits()"
                        />
                    </div>
                    <div style="min-width: 180px;">
                        <select class="doitly-input" id="categoryFilter" onchange="filterHabits()">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="min-width: 150px;">
                        <select class="doitly-input" id="timeFilter" onchange="filterHabits()">
                            <option value="">Todos os horários</option>
                            <option value="Manhã">☀️ Manhã</option>
                            <option value="Tarde">🌤️ Tarde</option>
                            <option value="Noite">🌙 Noite</option>
                        </select>
                    </div>
                    <button class="doitly-btn doitly-btn-ghost" onclick="clearFilters()">
                        <i class="bi bi-x-circle"></i>
                        Limpar
                    </button>
                </div>
            </div>
        </div>

        <!-- Habits List -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-list-check"></i>
                    Hábitos de Hoje
                </h3>
                <div class="card-actions">
                    <span class="doitly-badge doitly-badge-info" id="habitCount">
                        <?php echo count($habits); ?> hábitos
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div id="habitsList" class="d-flex flex-column gap-md">
                    <?php if (count($habits) > 0): ?>
                        <?php foreach ($habits as $habit): ?>
                            <div class="habit-item habit-card" 
                                 data-id="<?php echo $habit['id']; ?>"
                                 data-category="<?php echo htmlspecialchars($habit['category']); ?>"
                                 data-time="<?php echo htmlspecialchars($habit['time']); ?>"
                                 data-name="<?php echo htmlspecialchars(strtolower($habit['name'])); ?>"
                                 style="<?php echo $habit['completed_today'] ? 'opacity: 0.8;' : ''; ?>">
                                
                                <!-- Left Side: Info -->
                                <div class="d-flex align-items-center gap-md flex-grow-1" style="min-width: 0;">
                                    <!-- Color Indicator -->
                                    <div style="width: 4px; height: 48px; background: <?php echo $habit['color']; ?>; border-radius: 4px; flex-shrink: 0;"></div>
                                    
                                    <!-- Habit Info -->
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="d-flex align-items-center gap-sm" style="flex-wrap: wrap; margin-bottom: 4px;">
                                            <h4 style="margin: 0; font-size: 1rem; font-weight: var(--font-medium); <?php echo $habit['completed_today'] ? 'text-decoration: line-through;' : ''; ?>">
                                                <?php echo htmlspecialchars($habit['name']); ?>
                                            </h4>
                                            
                                            <?php if ($habit['time'] === 'Manhã'): ?>
                                                <span class="doitly-badge doitly-badge-success" style="font-size: 0.75rem;">☀️ Manhã</span>
                                            <?php elseif ($habit['time'] === 'Tarde'): ?>
                                                <span class="doitly-badge doitly-badge-info" style="font-size: 0.75rem;">🌤️ Tarde</span>
                                            <?php else: ?>
                                                <span class="doitly-badge doitly-badge-warning" style="font-size: 0.75rem;">🌙 Noite</span>
                                            <?php endif; ?>
                                            <?php if ($habit['frequency'] === 'daily'): ?>
                                                <span class="doitly-badge doitly-badge-info" style="font-size: 0.75rem;">Diário</span>
                                            <?php elseif ($habit['frequency'] === 'weekly'): ?>
                                                <span class="doitly-badge doitly-badge-warning" style="font-size: 0.75rem;">Semanal</span>
                                            <?php else: ?>
                                                <span class="doitly-badge doitly-badge-success" style="font-size: 0.75rem;">Custom</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($habit['description'])): ?>
                                            <p style="margin: 0 0 4px 0; font-size: 0.875rem; color: var(--text-secondary);">
                                                <?php echo htmlspecialchars($habit['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex align-items-center gap-md" style="flex-wrap: wrap;">
                                            <small class="text-secondary">
                                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($habit['category']); ?>
                                            </small>
                                            <small class="text-secondary">
                                                <i class="bi bi-bullseye"></i>
                                                <?php if (($habit['goal_type'] ?? 'completion') === 'completion'): ?>
                                                    Meta: concluir
                                                <?php else: ?>
                                                    Meta: <?php echo (int)$habit['goal_value']; ?> <?php echo htmlspecialchars($habit['goal_unit'] ?: 'unidades'); ?>
                                                <?php endif; ?>
                                            </small>
                                            <small style="color: var(--accent-gold); font-weight: var(--font-medium);">
                                                <i class="bi bi-fire"></i> <?php echo $habit['streak']; ?> dias
                                            </small>
                                            <small class="text-secondary">
                                                <i class="bi bi-calendar-event"></i> Próxima: <?php echo formatDateBr($habit['next_due_date'] ?? null); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right Side: Actions -->
                                <div class="d-flex align-items-center gap-sm" style="flex-shrink: 0;">
                                    <form method="POST" action="../actions/habit_mark_action.php" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                        <input type="hidden" name="completion_date" value="<?php echo date('Y-m-d'); ?>">
                                        <?php if (($habit['goal_type'] ?? 'completion') !== 'completion' && !$habit['completed_today']): ?>
                                            <input type="number" step="0.01" min="0" name="value_achieved" class="doitly-input" style="width: 100px; padding: 6px 8px;" placeholder="valor" required>
                                        <?php endif; ?>
                                        <?php if ($habit['completed_today']): ?>
                                            <button type="submit" class="doitly-btn doitly-btn-sm doitly-btn-success">
                                                <i class="bi bi-check-circle-fill"></i> Feito
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="doitly-btn doitly-btn-sm doitly-btn-success">
                                                <i class="bi bi-circle"></i> Concluir
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <button class="doitly-btn doitly-btn-sm doitly-btn-ghost" onclick="openEditModal(<?php echo $habit['id']; ?>)" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form method="POST" action="../actions/habit_archive_action.php" style="display: inline;">
                                        <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                        <input type="hidden" name="operation" value="archive">
                                        <button type="submit" class="doitly-btn doitly-btn-sm doitly-btn-ghost" title="Arquivar">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                    
                                    <button class="doitly-btn doitly-btn-sm doitly-btn-ghost" onclick="confirmDelete(<?php echo $habit['id']; ?>, '<?php echo htmlspecialchars($habit['name'], ENT_QUOTES); ?>')" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <h4 class="empty-title">Nenhum hábito para hoje</h4>
                            <p class="empty-text">Hoje não há hábitos programados. Veja a seção semanal abaixo.</p>
                            <button class="doitly-btn" onclick="openHabitModal('create')">
                                <i class="bi bi-plus-circle"></i>
                                Criar Primeiro Hábito
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Empty State (para filtros) -->
                <div id="emptyState" class="empty-state" style="display: none;">
                    <div class="empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h4 class="empty-title">Nenhum hábito encontrado</h4>
                    <p class="empty-text">Tente ajustar os filtros ou criar um novo hábito</p>
                </div>
            </div>
        </div>


        <div class="dashboard-card" style="margin-top: var(--space-lg);">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-calendar-week"></i> Hábitos por Dia da Semana</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-md">
                    <?php foreach ($habitsByWeekDay as $weekDayData): ?>
                        <div class="habit-item" style="align-items: flex-start;">
                            <div style="min-width: 140px;">
                                <strong><?php echo $weekDayData['label']; ?></strong>
                                <small class="text-secondary d-block"><?php echo count($weekDayData['habits']); ?> hábitos</small>
                            </div>
                            <div class="d-flex gap-sm" style="flex-wrap: wrap;">
                                <?php if (count($weekDayData['habits']) === 0): ?>
                                    <span class="text-secondary">Nenhum hábito programado</span>
                                <?php else: ?>
                                    <?php foreach ($weekDayData['habits'] as $dayHabit): ?>
                                        <span class="doitly-badge doitly-badge-info" style="font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($dayHabit['name']); ?>
                                            <?php if (($dayHabit['frequency'] ?? 'daily') === 'daily'): ?>
                                                <strong>(Diário)</strong>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="dashboard-card" style="margin-top: var(--space-lg);">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-archive"></i> Hábitos Arquivados</h3>
            </div>
            <div class="card-body">
                <?php if (count($archivedHabits) === 0): ?>
                    <p class="text-secondary" style="margin: 0;">Nenhum hábito arquivado.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-sm">
                        <?php foreach ($archivedHabits as $archivedHabit): ?>
                            <div class="habit-item" style="opacity: .85;">
                                <div>
                                    <strong><?php echo htmlspecialchars($archivedHabit['name']); ?></strong>
                                    <small class="text-secondary d-block"><?php echo htmlspecialchars($archivedHabit['category']); ?></small>
                                </div>
                                <form method="POST" action="../actions/habit_archive_action.php" style="display: inline;">
                                    <input type="hidden" name="habit_id" value="<?php echo $archivedHabit['id']; ?>">
                                    <input type="hidden" name="operation" value="restore">
                                    <button type="submit" class="doitly-btn doitly-btn-sm doitly-btn-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include_once "includes/settings_modal.php"; ?>

<!-- Modal: Criar/Editar Hábito -->
<div id="habitModal" style="display: none; position: fixed; inset: 0; background: var(--overlay-backdrop); backdrop-filter: blur(4px); z-index: 1000; padding: var(--space-lg); overflow-y: auto;">
    <div style="max-width: 600px; margin: 40px auto; background: var(--bg-light); border-radius: var(--radius-large); padding: var(--space-xl); box-shadow: var(--shadow-strong);">
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom: var(--space-lg);">
            <h2 style="margin: 0; font-size: 1.5rem;" id="modalTitle">
                <i class="bi bi-plus-circle"></i> Novo Hábito
            </h2>
            <button class="doitly-btn doitly-btn-ghost doitly-btn-sm" onclick="closeHabitModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="habitForm" method="POST" action="../actions/habit_create_action.php">
            <input type="hidden" name="habit_id" id="habitId" value="">
            
            <div style="margin-bottom: var(--space-md);">
                <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                    Nome do Hábito *
                </label>
                <input 
                    type="text" 
                    name="title"
                    class="doitly-input" 
                    placeholder="Ex: Meditar pela manhã" 
                    required
                    id="habitName"
                />
            </div>

            <div style="margin-bottom: var(--space-md);">
                <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                    Descrição (Opcional)
                </label>
                <textarea 
                    name="description"
                    class="doitly-input doitly-textarea" 
                    placeholder="Adicione detalhes sobre seu hábito..."
                    id="habitDescription"
                    rows="3"
                ></textarea>
            </div>

            <div class="row g-3" style="margin-bottom: var(--space-md);">
                <div class="col-md-6">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                        Categoria *
                    </label>
                    <select name="category" class="doitly-input" required id="habitCategory">
                        <option value="">Selecione...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                        Horário *
                    </label>
                    <select name="time" class="doitly-input" required id="habitTime">
                        <option value="">Selecione...</option>
                        <option value="Manhã">☀️ Manhã (06:00 - 12:00)</option>
                        <option value="Tarde">🌤️ Tarde (12:00 - 18:00)</option>
                        <option value="Noite">🌙 Noite (18:00 - 00:00)</option>
                    </select>
                </div>
            </div>


            <div class="row g-3" style="margin-bottom: var(--space-md);">
                <div class="col-md-6">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                        Frequência
                    </label>
                    <select name="frequency" class="doitly-input" id="habitFrequency" onchange="toggleTargetDays()">
                        <option value="daily">Diário</option>
                        <option value="weekly">Semanal</option>
                        <option value="custom">Customizado</option>
                    </select>
                </div>
                <div class="col-md-6" id="targetDaysWrapper" style="display: none;">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                        Dias da Semana
                    </label>
                    <div class="d-flex gap-sm" style="flex-wrap: wrap;">
                        <?php $dayLabels = ['D','S','T','Q','Q','S','S']; ?>
                        <?php for ($day = 0; $day <= 6; $day++): ?>
                            <label style="display:flex; align-items:center; gap:4px; font-size:.85rem;">
                                <input type="checkbox" name="target_days[]" value="<?php echo $day; ?>" class="target-day-option">
                                <span><?php echo $dayLabels[$day]; ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="row g-3" style="margin-bottom: var(--space-md);">
                <div class="col-md-4">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">Tipo de Meta</label>
                    <select name="goal_type" class="doitly-input" id="habitGoalType" onchange="toggleGoalFields()">
                        <option value="completion">Concluir</option>
                        <option value="quantity">Quantidade</option>
                        <option value="duration">Duração</option>
                    </select>
                </div>
                <div class="col-md-4" id="goalValueWrapper" style="display:none;">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">Valor</label>
                    <input type="number" min="1" name="goal_value" id="habitGoalValue" class="doitly-input" value="1">
                </div>
                <div class="col-md-4" id="goalUnitWrapper" style="display:none;">
                    <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">Unidade</label>
                    <input type="text" name="goal_unit" id="habitGoalUnit" class="doitly-input" placeholder="min, litros, reps...">
                </div>
            </div>
            <div style="margin-bottom: var(--space-lg);">
                <label class="form-label text-secondary" style="display: block; margin-bottom: 8px; font-weight: var(--font-medium);">
                    Cor do Hábito
                </label>
                <div class="d-flex gap-sm" style="flex-wrap: wrap;">
                    <label style="cursor: pointer;">
                        <input type="radio" name="color" value="#4a74ff" checked style="display: none;">
                        <div style="width: 40px; height: 40px; background: #4a74ff; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="color" value="#59d186" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #59d186; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="color" value="#ff5757" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #ff5757; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="color" value="#eed27a" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #eed27a; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="color" value="#a78bfa" style="display: none;">
                        <div style="width: 40px; height: 40px; background: #a78bfa; border-radius: var(--radius-small); border: 3px solid transparent; transition: var(--transition);" class="color-option"></div>
                    </label>
                </div>
            </div>

            <div class="d-flex gap-md">
                <button type="button" class="doitly-btn doitly-btn-secondary flex-grow-1" onclick="closeHabitModal()">
                    Cancelar
                </button>
                <button type="submit" class="doitly-btn flex-grow-1">
                    <i class="bi bi-save"></i> Salvar Hábito
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Form de Delete (escondido) -->
<form id="deleteForm" method="POST" action="../actions/habit_delete_action.php" style="display: none;">
    <input type="hidden" name="habit_id" id="deleteHabitId">
</form>

<style>
.habit-card {
    transition: all 0.3s ease;
}

.color-option {
    transition: all 0.2s ease;
}

.color-option:hover {
    transform: scale(1.1);
}

input[type="radio"]:checked + .color-option {
    border-color: var(--text-primary) !important;
    transform: scale(1.15);
}

@media (max-width: 768px) {
    .habit-item {
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .habit-item > div:last-child {
        justify-content: flex-start;
        margin-top: var(--space-sm);
    }
}
</style>

<script>
// Dados dos hábitos em JSON para JavaScript
const habitsData = <?php echo json_encode($habits); ?>;

function toggleTargetDays() {
    const frequency = document.getElementById('habitFrequency')?.value;
    const wrapper = document.getElementById('targetDaysWrapper');
    if (!wrapper) return;
    wrapper.style.display = (frequency === 'weekly' || frequency === 'custom') ? 'block' : 'none';
}

function toggleGoalFields() {
    const goalType = document.getElementById('habitGoalType')?.value;
    const valueWrapper = document.getElementById('goalValueWrapper');
    const unitWrapper = document.getElementById('goalUnitWrapper');
    const goalValue = document.getElementById('habitGoalValue');

    const show = goalType !== 'completion';
    valueWrapper.style.display = show ? 'block' : 'none';
    unitWrapper.style.display = show ? 'block' : 'none';

    if (goalValue) {
        goalValue.required = show;
    }
}


function openHabitModal(mode = 'create') {
    document.getElementById('habitModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    if (mode === 'create') {
        document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Novo Hábito';
        document.getElementById('habitForm').action = '../actions/habit_create_action.php';
        document.getElementById('habitForm').reset();
        document.getElementById('habitId').value = '';
        document.getElementById('habitFrequency').value = 'daily';
        document.getElementById('habitGoalType').value = 'completion';
        toggleTargetDays();
        toggleGoalFields();
    }
}

function openEditModal(habitId) {
    // Buscar dados do hábito
    const habit = habitsData.find(h => h.id == habitId);
    
    if (!habit) {
        alert('Hábito não encontrado!');
        return;
    }
    
    // Preencher formulário
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil"></i> Editar Hábito';
    document.getElementById('habitForm').action = '../actions/habit_update_action.php';
    document.getElementById('habitId').value = habit.id;
    document.getElementById('habitName').value = habit.name;
    document.getElementById('habitDescription').value = habit.description || '';
    document.getElementById('habitCategory').value = habit.category;
    document.getElementById('habitTime').value = habit.time;
    document.getElementById('habitFrequency').value = habit.frequency || 'daily';
    document.getElementById('habitGoalType').value = habit.goal_type || 'completion';
    document.getElementById('habitGoalValue').value = habit.goal_value || 1;
    document.getElementById('habitGoalUnit').value = habit.goal_unit || '';

    const dayCheckboxes = document.querySelectorAll('.target-day-option');
    dayCheckboxes.forEach((checkbox) => {
        checkbox.checked = Array.isArray(habit.target_days) && habit.target_days.includes(Number(checkbox.value));
    });

    toggleTargetDays();
    toggleGoalFields();
    
    // Selecionar cor
    const colorRadios = document.querySelectorAll('input[name="color"]');
    colorRadios.forEach(radio => {
        if (radio.value === habit.color) {
            radio.checked = true;
        }
    });
    
    // Abrir modal
    openHabitModal('edit');
}

function closeHabitModal() {
    document.getElementById('habitModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('habitForm').reset();
    toggleTargetDays();
    toggleGoalFields();
}

function confirmDelete(habitId, habitName) {
    if (confirm(`Tem certeza que deseja excluir o hábito "${habitName}"?\n\nEsta ação não pode ser desfeita e todas as conclusões serão perdidas.`)) {
        document.getElementById('deleteHabitId').value = habitId;
        document.getElementById('deleteForm').submit();
    }
}

function filterHabits() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const timeFilter = document.getElementById('timeFilter').value;
    
    const habits = document.querySelectorAll('.habit-card');
    let visibleCount = 0;
    
    habits.forEach(habit => {
        const name = habit.dataset.name;
        const category = habit.dataset.category;
        const time = habit.dataset.time;
        
        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        const matchesTime = !timeFilter || time === timeFilter;
        
        if (matchesSearch && matchesCategory && matchesTime) {
            habit.style.display = 'flex';
            visibleCount++;
        } else {
            habit.style.display = 'none';
        }
    });
    
    // Atualizar contador
    document.getElementById('habitCount').textContent = visibleCount + ' hábito' + (visibleCount !== 1 ? 's' : '');
    
    // Mostrar/ocultar empty state
    const habitsList = document.getElementById('habitsList');
    const emptyState = document.getElementById('emptyState');
    
    if (visibleCount === 0 && habits.length > 0) {
        habitsList.style.display = 'none';
        emptyState.style.display = 'block';
    } else {
        habitsList.style.display = 'flex';
        emptyState.style.display = 'none';
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('timeFilter').value = '';
    filterHabits();
}

// Fechar modal ao clicar fora
document.getElementById('habitModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeHabitModal();
    }
});

toggleTargetDays();
toggleGoalFields();

// Auto-hide alerts após 5 segundos
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>

<?php include_once "includes/footer.php"; ?>
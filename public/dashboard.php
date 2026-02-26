<?php
// Proteger página - requer login
require_once '../config/bootstrap.php';

use App\Api\Internal\StatsApiPayloadBuilder;
use App\Support\UserLocalDateResolver;
use App\UserProgress\UserProgressService;
bootApp();

requireAuthenticatedUser();

// Configurações da página
$showRegisterButton = false;
$hideLoginButton = true;

// Buscar dados do usuário logado
$userId = getAuthenticatedUserId();
$userData = getAuthenticatedUserRecord($conn);

// Se não encontrou usuário, fazer logout
if (!$userData) {
    signOutUser();
}

// Adicionar iniciais ao userData
$userData['initials'] = getUserInitials($userData['name']);
$rawUserName = trim((string) ($userData['name'] ?? ''));
$nameParts = preg_split('/\s+/', $rawUserName);
$firstName = (is_array($nameParts) && !empty($nameParts[0])) ? $nameParts[0] : 'Usuário';
$firstNameEscaped = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');

$userProgressService = new UserProgressService($conn);
$profileSummary = $userProgressService->refreshUserProgressSummary((int) $userId);
$userData['level'] = (int) ($profileSummary['level'] ?? 1);

// Carregar dados centralizados pela API (somente via PHP interno)
$dashboardPayload = StatsApiPayloadBuilder::build($conn, (int) $userId, 'dashboard');
$stats = $dashboardPayload['data']['stats'] ?? [];
$todayHabits = $dashboardPayload['data']['today_habits'] ?? [];
$weeklyData = $dashboardPayload['data']['weekly_data'] ?? ['labels' => [], 'completed' => [], 'total' => []];

$adaptiveRecommendation = $dashboardPayload['data']['adaptive_recommendation'] ?? [];
$recommendationMeta = $adaptiveRecommendation['recommendation'] ?? [];
$recommendationActions = $recommendationMeta['actions'] ?? [];
$recommendationRisk = $adaptiveRecommendation['risk_level'] ?? 'stable';
$recommendationTrend = $adaptiveRecommendation['trend'] ?? 'neutral';

$recommendationProfiles = [
    'high_performer' => [
        'level' => 'Fase de evolução',
        'focus' => 'Você já tem constância. O foco agora é ampliar resultado sem perder consistência.',
        'next_24h' => 'Escolha um único ajuste de dificuldade para testar hoje.',
        'next_week' => 'Aumente gradualmente a meta de 1 hábito e acompanhe por 7 dias.',
        'metrics' => [
            'Indicador principal' => 'Expansão sustentável',
            'Carga recomendada' => 'Leve aumento de desafio',
            'Ritmo sugerido' => 'Progressivo'
        ]
    ],
    'stable' => [
        'level' => 'Fase de consolidação',
        'focus' => 'Seu ritmo está bom. O próximo passo é reduzir fricções para ganhar previsibilidade.',
        'next_24h' => 'Mantenha os horários dos hábitos mais concluídos e ajuste apenas 1 hábito oscilante.',
        'next_week' => 'Revise a rotina no fim da semana para remover tarefas que geram atrito.',
        'metrics' => [
            'Indicador principal' => 'Consistência',
            'Carga recomendada' => 'Manutenção com pequenos ajustes',
            'Ritmo sugerido' => 'Estável'
        ]
    ],
    'attention' => [
        'level' => 'Fase de recuperação',
        'focus' => 'Houve oscilação recente. Simplificar a execução agora aumenta a chance de retomada.',
        'next_24h' => 'Transforme o hábito principal em uma versão mínima que leve até 2 minutos.',
        'next_week' => 'Priorize completar menos hábitos, mas sem quebrar a sequência diária.',
        'metrics' => [
            'Indicador principal' => 'Retomada do ritmo',
            'Carga recomendada' => 'Redução temporária',
            'Ritmo sugerido' => 'Constância antes de intensidade'
        ]
    ],
    'at_risk' => [
        'level' => 'Fase de proteção de rotina',
        'focus' => 'O objetivo agora é evitar abandono e reconstruir confiança com metas mínimas.',
        'next_24h' => 'Escolha apenas o hábito mais importante e conclua a versão mais simples possível.',
        'next_week' => 'Use metas reduzidas por 5 a 7 dias até recuperar regularidade.',
        'metrics' => [
            'Indicador principal' => 'Adesão mínima diária',
            'Carga recomendada' => 'Mínima e viável',
            'Ritmo sugerido' => 'Recomeço assistido'
        ]
    ]
];

$recommendationProfile = $recommendationProfiles[$recommendationRisk] ?? $recommendationProfiles['stable'];

$riskConfig = [
    'high_performer' => ['label' => 'Alto desempenho', 'class' => 'risk-high', 'icon' => 'bi-rocket-takeoff'],
    'stable' => ['label' => 'Estável', 'class' => 'risk-stable', 'icon' => 'bi-check2-circle'],
    'attention' => ['label' => 'Atenção', 'class' => 'risk-attention', 'icon' => 'bi-exclamation-circle'],
    'at_risk' => ['label' => 'Risco de abandono', 'class' => 'risk-danger', 'icon' => 'bi-exclamation-triangle']
];

$trendConfig = [
    'positive' => ['label' => 'Tendência positiva', 'icon' => 'bi-graph-up-arrow'],
    'neutral' => ['label' => 'Tendência estável', 'icon' => 'bi-dash-circle'],
    'negative' => ['label' => 'Tendência negativa', 'icon' => 'bi-graph-down-arrow']
];

$riskView = $riskConfig[$recommendationRisk] ?? $riskConfig['stable'];
$trendView = $trendConfig[$recommendationTrend] ?? $trendConfig['neutral'];

$completionChange = $stats['completion_change'] ?? ['status' => 'insufficient', 'label' => 'Dados insuficientes', 'icon' => 'bi-dash'];
$completionChangeStatus = $completionChange['status'] ?? 'insufficient';
$completionChangeClassMap = [
    'up' => 'positive',
    'down' => 'negative',
    'stable' => 'neutral',
    'insufficient' => 'neutral'
];
$completionChangeClass = $completionChangeClassMap[$completionChangeStatus] ?? 'neutral';
$completionChangeIcon = $completionChange['icon'] ?? 'bi-dash';
$completionChangeLabel = $completionChange['label'] ?? 'Dados insuficientes';

$weeklyChartLabels = $weeklyData['labels'] ?? [];
$weeklyChartCompleted = $weeklyData['completed'] ?? [];

if (count($weeklyChartLabels) === 0) {
    $weeklyChartLabels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
    $weeklyChartCompleted = array_fill(0, 7, 0);
}

$monthSummary = [
    'active_days' => (int) ($stats['active_days'] ?? 0),
    'total_days' => max(1, (int) ($stats['tracked_days'] ?? 0)),
    'best_streak' => (int) ($stats['best_streak'] ?? 0),
    'total_completions' => (int) ($stats['total_completions'] ?? 0)
];

$csrfToken = getCsrfToken();
$userLocalDateResolver = new UserLocalDateResolver($conn);
$userTodayDate = $userLocalDateResolver->getTodayDateForUser((int) $userId);

include_once "includes/header.php";
?>

<?php include __DIR__ . '/includes/partials/dashboard_head_assets.php'; ?>

<!-- Dashboard Wrapper -->
<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <!-- User Info -->
        <?php include __DIR__ . '/includes/partials/sidebar_user_card.php'; ?>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h5 class="nav-section-title">Menu Principal</h5>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="bi bi-house-door"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="habits.php" class="nav-link">
                            <i class="bi bi-list-check"></i>
                            <span>Meus Hábitos</span>
                            <span class="nav-badge"><?php echo $stats['total_habits']; ?></span>
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
                        <a href="#" class="nav-link" data-open-settings-modal aria-controls="settingsModalOverlay"
                            aria-haspopup="dialog">
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
            <div class="alert alert-success alert-success-theme"
                style="margin-bottom: var(--space-lg); padding: var(--space-md); border-radius: var(--radius-medium);">
                <i class="bi bi-check-circle"></i>
                <?php echo htmlspecialchars((string) $_SESSION['success_message'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-danger-theme"
                style="margin-bottom: var(--space-lg); padding: var(--space-md); border-radius: var(--radius-medium);">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo htmlspecialchars((string) $_SESSION['error_message'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Bem-vindo de volta, <?php echo $firstNameEscaped; ?>! 👋</h1>
            <p class="dashboard-subtitle">Aqui está um resumo do seu progresso hoje</p>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Hábitos Ativos</span>
                    <div class="stat-icon">
                        <i class="bi bi-list-check"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['total_habits']; ?></h2>
                <div class="stat-change neutral">
                    <i class="bi bi-dash"></i>
                    <span>Total cadastrado</span>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-header">
                    <span class="stat-label">Concluídos Hoje</span>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['completed_today']; ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>de <?php echo $stats['total_habits']; ?> hábitos</span>
                </div>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-header">
                    <span class="stat-label">Taxa de Sucesso Hoje</span>
                    <div class="stat-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo (int) ($stats['today_rate'] ?? 0); ?>%</h2>
                <div class="stat-change <?php echo $completionChangeClass; ?>">
                    <i class="bi <?php echo $completionChangeIcon; ?>"></i>
                    <span><?php echo htmlspecialchars($completionChangeLabel); ?></span>
                </div>
            </div>

            <div class="stat-card stat-danger">
                <div class="stat-header">
                    <span class="stat-label">Sequência Atual</span>
                    <div class="stat-icon">
                        <i class="bi bi-fire"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['current_streak']; ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>dias seguidos</span>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Progress Chart -->
            <div class="grid-col-8">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-bar-chart-line"></i>
                            Progresso Semanal
                        </h3>
                        <div class="card-actions">
                            <a href="../actions/export_user_data_csv_action.php" class="doitly-btn doitly-btn-sm doitly-btn-ghost" title="Exportar resumo em CSV">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="weeklyProgressChart"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid-col-4">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-lightning"></i>
                            Ações Rápidas
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-md">
                            <a href="habits.php" class="doitly-btn w-100">
                                <i class="bi bi-plus-circle"></i>
                                Novo Hábito
                            </a>
                            <a href="history.php" class="doitly-btn doitly-btn-secondary w-100">
                                <i class="bi bi-graph-up"></i>
                                Ver Estatísticas
                            </a>
                            <a href="../actions/export_user_data_csv_action.php" class="doitly-btn doitly-btn-outline w-100">
                                <i class="bi bi-download"></i>
                                Exportar dados
                            </a>
                        </div>

                        <!-- Mini Stats -->
                        <div
                            style="margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: var(--border-light);">
                            <h4
                                style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: var(--space-md);">
                                Resumo Geral
                            </h4>
                            <div class="d-flex flex-column gap-sm">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Dias ativos no histórico</span>
                                    <strong
                                        style="color: var(--accent-green);"><?php echo $monthSummary['active_days']; ?>/<?php echo $monthSummary['total_days']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Melhor
                                        sequência</span>
                                    <strong
                                        style="color: var(--accent-blue);"><?php echo $monthSummary['best_streak']; ?>
                                        dias</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Total
                                        concluído</span>
                                    <strong
                                        style="color: var(--accent-gold);"><?php echo $monthSummary['total_completions']; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <div class="grid-col-12">
                <div class="dashboard-card recommendation-card <?php echo $riskView['class']; ?>">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-cpu"></i>
                            Análise Inteligente
                        </h3>
                        <div class="card-actions recommendation-header-badges">
                            <span class="doitly-badge recommendation-badge">
                                <i class="bi <?php echo $riskView['icon']; ?>"></i>
                                <?php echo $riskView['label']; ?>
                            </span>
                            <span class="doitly-badge recommendation-badge recommendation-badge-secondary">
                                <i class="bi <?php echo $trendView['icon']; ?>"></i>
                                <?php echo $trendView['label']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="recommendation-insight">
                            <?php echo htmlspecialchars($recommendationMeta['insight_text'] ?? 'Ainda não há dados suficientes. Continue concluindo hábitos para gerar recomendações personalizadas.', ENT_QUOTES, 'UTF-8'); ?>
                        </p>

                        <div class="recommendation-context">
                            <div class="recommendation-context-header">
                                <h4><?php echo htmlspecialchars($recommendationProfile['level'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                <p><?php echo htmlspecialchars($recommendationProfile['focus'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="recommendation-context-grid">
                                <article>
                                    <h5>Próximas 24h</h5>
                                    <p><?php echo htmlspecialchars($recommendationProfile['next_24h'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </article>
                                <article>
                                    <h5>Plano para 7 dias</h5>
                                    <p><?php echo htmlspecialchars($recommendationProfile['next_week'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </article>
                            </div>
                        </div>

                        <?php if (!empty($recommendationActions)): ?>
                            <h4 class="recommendation-section-title">Recomendações práticas</h4>
                            <ul class="recommendation-actions">
                                <?php foreach ($recommendationActions as $action): ?>
                                    <li>
                                        <i class="bi bi-check2"></i>
                                        <span><?php echo htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="recommendation-footer">
                            <div class="recommendation-metrics">
                                <?php foreach ($recommendationProfile['metrics'] as $label => $value): ?>
                                    <span><strong><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <a href="habits.php" class="doitly-btn doitly-btn-sm">
                                <i class="bi bi-magic"></i>
                                Ajustar meus hábitos
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Habits -->
            <div class="grid-col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-calendar-check"></i>
                            Hábitos de Hoje
                        </h3>
                        <div class="card-actions">
                            <span class="doitly-badge doitly-badge-info">
                                <?php echo $stats['completed_today']; ?>/<?php echo count($todayHabits); ?> concluídos
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-md">
                            <?php foreach ($todayHabits as $habit): ?>
                                <div class="habit-item" style="<?php echo $habit['completed'] ? 'opacity: 0.7;' : ''; ?>">
                                    <div class="d-flex align-items-center gap-md flex-grow-1">
                                        <?php if ($habit['time'] === 'Manhã'): ?>
                                            <span class="doitly-badge doitly-badge-success">☀️ Manhã</span>
                                        <?php elseif ($habit['time'] === 'Tarde'): ?>
                                            <span class="doitly-badge doitly-badge-info">🌤️ Tarde</span>
                                        <?php else: ?>
                                            <span class="doitly-badge doitly-badge-warning">🌙 Noite</span>
                                        <?php endif; ?>

                                        <div class="flex-grow-1">
                                            <span class="d-block"
                                                style="<?php echo $habit['completed'] ? 'text-decoration: line-through;' : ''; ?>">
                                                <?php echo htmlspecialchars($habit['name']); ?>
                                            </span>
                                            <small class="text-secondary" style="display: block;">
                                                <?php echo htmlspecialchars($habit['category']); ?>
                                            </small>
                                            <small class="text-secondary" style="display: block;">
                                                <i class="bi bi-bullseye"></i>
                                                <?php if (($habit['goal_type'] ?? 'completion') === 'completion'): ?>
                                                    Meta: concluir
                                                <?php else: ?>
                                                    Meta: <?php echo (int) ($habit['goal_value'] ?? 1); ?> <?php echo htmlspecialchars(($habit['goal_unit'] ?? '') ?: 'unidades'); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <?php if ($habit['completed']): ?>
                                        <button class="doitly-btn doitly-btn-sm doitly-btn-success" disabled>
                                            <i class="bi bi-check-circle-fill"></i> Concluído
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="../actions/habit_toggle_completion_action.php" class="habit-completion-form" style="display: inline-flex; align-items: center; gap: 6px;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="completion_date" value="<?php echo $userTodayDate; ?>">
                                            <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                            <?php if (($habit['goal_type'] ?? 'completion') !== 'completion'): ?>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    name="value_achieved"
                                                    class="doitly-input"
                                                    style="width: 100px; padding: 6px 8px;"
                                                    placeholder="valor"
                                                    required>
                                            <?php endif; ?>
                                            <button type="submit" class="doitly-btn doitly-btn-sm">
                                                <i class="bi bi-circle"></i> Concluir
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($todayHabits) === 0): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <h4 class="empty-title">Nenhum hábito para hoje</h4>
                                <p class="empty-text">Comece criando seus primeiros hábitos!</p>
                                <a href="habits.php" class="doitly-btn">
                                    <i class="bi bi-plus-circle"></i>
                                    Criar Primeiro Hábito
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once "includes/settings_modal.php"; ?>
<?php include_once "includes/profile_modal.php"; ?>

<!-- Chart Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const getThemeChartOptions = () => {
            const styles = getComputedStyle(document.documentElement);
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                accentBlue: styles.getPropertyValue('--accent-blue').trim() || '#4a74ff',
                textSecondary: styles.getPropertyValue('--text-secondary').trim() || '#6c757d',
                border: isDark ? 'rgba(255,255,255,0.18)' : 'rgba(0, 0, 0, 0.08)',
                markerStroke: isDark ? '#161b22' : '#fff',
                tooltipTheme: isDark ? 'dark' : 'light'
            };
        };

        const buildOptions = () => {
            const theme = getThemeChartOptions();
            return {
            series: [{
                name: 'Hábitos Concluídos',
                data: <?php echo json_encode($weeklyChartCompleted); ?>
            }],
            chart: {
                type: 'area',
                height: 320,
                fontFamily: 'Inter, sans-serif',
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            colors: [theme.accentBlue],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: <?php echo json_encode($weeklyChartLabels); ?>,
                labels: {
                    style: {
                        colors: theme.textSecondary,
                        fontSize: '13px'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                title: {
                    text: 'Hábitos',
                    style: {
                        color: theme.textSecondary,
                        fontSize: '13px',
                        fontWeight: 500
                    }
                },
                labels: {
                    style: {
                        colors: theme.textSecondary,
                        fontSize: '13px'
                    }
                }
            },
            grid: {
                borderColor: theme.border,
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: false
                    }
                }
            },
            tooltip: {
                theme: theme.tooltipTheme,
                y: {
                    formatter: function (value) {
                        return value + ' hábitos'
                    }
                },
                style: {
                    fontSize: '13px',
                    fontFamily: 'Inter, sans-serif'
                }
            },
            markers: {
                size: 5,
                colors: [theme.accentBlue],
                strokeColors: theme.markerStroke,
                strokeWidth: 2,
                hover: {
                    size: 7
                }
            }
        };
        };

        let chart = new ApexCharts(document.querySelector("#weeklyProgressChart"), buildOptions());
        chart.render();

        window.addEventListener('doitly:theme-change', () => {
            chart.destroy();
            chart = new ApexCharts(document.querySelector("#weeklyProgressChart"), buildOptions());
            chart.render();
        });

        // Auto-hide alerts após 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    });
</script>

<style>
@media (max-width: 768px) {
    .dashboard-content .habit-item > .doitly-btn,
    .dashboard-content .habit-item > form {
        width: 100%;
    }

    .dashboard-content .habit-item > form.habit-completion-form {
        display: flex !important;
        flex-wrap: wrap;
        gap: var(--space-xs) !important;
    }

    .dashboard-content .habit-item > form.habit-completion-form .doitly-input[type="number"] {
        width: 100% !important;
        min-width: 0;
    }

    .dashboard-content .habit-item > form.habit-completion-form .doitly-btn {
        width: 100%;
    }
}
</style>

<?php include_once "includes/footer.php"; ?>

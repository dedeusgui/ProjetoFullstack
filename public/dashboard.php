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

// Carregar dados centralizados pela API (somente via PHP interno)
if (!defined('DOITLY_INTERNAL_API_CALL')) {
    define('DOITLY_INTERNAL_API_CALL', true);
}
require_once '../actions/api_get_stats.php';

$dashboardPayload = buildStatsApiResponse($conn, (int) $userId, 'dashboard');
$stats = $dashboardPayload['data']['stats'] ?? [];
$todayHabits = $dashboardPayload['data']['today_habits'] ?? [];
$weeklyData = $dashboardPayload['data']['weekly_data'] ?? ['labels' => [], 'completed' => [], 'total' => []];

$weeklyChartLabels = $weeklyData['labels'] ?? [];
$weeklyChartCompleted = $weeklyData['completed'] ?? [];

if (count($weeklyChartLabels) === 0) {
    $weeklyChartLabels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
    $weeklyChartCompleted = array_fill(0, 7, 0);
}

$monthSummary = [
    'active_days' => (int) round((($stats['completion_rate'] ?? 0) / 100) * 30),
    'total_days' => 30,
    'best_streak' => getBestStreak($conn, $userId),
    'total_completions' => getTotalCompletions($conn, $userId)
];

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
                        <img src="<?php echo htmlspecialchars($userData['avatar_url'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="Avatar de <?php echo htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
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
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-danger-theme"
                style="margin-bottom: var(--space-lg); padding: var(--space-md); border-radius: var(--radius-medium);">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Bem-vindo de volta, <?php echo explode(' ', $userData['name'])[0]; ?>! 👋</h1>
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
                    <span class="stat-label">Taxa de Sucesso</span>
                    <div class="stat-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['completion_rate']; ?>%</h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>+5% esta semana</span>
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
                            <a href="../actions/export_user_data_csv.php" class="doitly-btn doitly-btn-sm doitly-btn-ghost" title="Exportar resumo em CSV">
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
                            <a href="../actions/export_user_data_csv.php" class="doitly-btn doitly-btn-outline w-100">
                                <i class="bi bi-download"></i>
                                Exportar Dados
                            </a>
                        </div>

                        <!-- Mini Stats -->
                        <div
                            style="margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: var(--border-light);">
                            <h4
                                style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: var(--space-md);">
                                Resumo do Mês
                            </h4>
                            <div class="d-flex flex-column gap-sm">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Dias ativos</span>
                                    <strong
                                        style="color: var(--accent-green);"><?php echo $monthSummary['active_days']; ?>/<?php echo $monthSummary['total_days']; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Melhor
                                        streak</span>
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
                                            <small
                                                class="text-secondary"><?php echo htmlspecialchars($habit['category']); ?></small>
                                        </div>
                                    </div>

                                    <?php if ($habit['completed']): ?>
                                        <button class="doitly-btn doitly-btn-sm doitly-btn-success" disabled>
                                            <i class="bi bi-check-circle-fill"></i> Concluído
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="../actions/habit_mark_action.php" style="display: inline;">
                                            <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                            <button type="submit" class="doitly-btn doitly-btn-sm">
                                                <i class="bi bi-circle"></i> Marcar
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

<?php include_once "includes/footer.php"; ?>
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
                    <?php echo $userData['initials']; ?>
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
                        <a href="#" class="nav-link">
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
                            <button class="doitly-btn doitly-btn-sm doitly-btn-ghost">
                                <i class="bi bi-download"></i>
                            </button>
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
                            <button class="doitly-btn doitly-btn-outline w-100">
                                <i class="bi bi-download"></i>
                                Exportar Dados
                            </button>
                        </div>

                        <!-- Mini Stats -->
                        <div style="margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: var(--border-light);">
                            <h4 style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: var(--space-md);">
                                Resumo do Mês
                            </h4>
                            <div class="d-flex flex-column gap-sm">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Dias ativos</span>
                                    <strong style="color: var(--accent-green);">23/30</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Melhor streak</span>
                                    <strong style="color: var(--accent-blue);">15 dias</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size: 0.875rem; color: var(--text-secondary);">Total concluído</span>
                                    <strong style="color: var(--accent-gold);">156</strong>
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
                                            <span class="d-block" style="<?php echo $habit['completed'] ? 'text-decoration: line-through;' : ''; ?>">
                                                <?php echo $habit['name']; ?>
                                            </span>
                                            <small class="text-secondary"><?php echo $habit['category']; ?></small>
                                        </div>
                                    </div>
                                    
                                    <?php if ($habit['completed']): ?>
                                        <button class="doitly-btn doitly-btn-sm doitly-btn-success" disabled>
                                            <i class="bi bi-check-circle-fill"></i> Concluído
                                        </button>
                                    <?php else: ?>
                                        <button class="doitly-btn doitly-btn-sm">
                                            <i class="bi bi-circle"></i> Marcar
                                        </button>
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

<!-- Chart Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var options = {
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
        colors: ['#4a74ff'],
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
                    colors: '#6c757d',
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
                    color: '#6c757d',
                    fontSize: '13px',
                    fontWeight: 500
                }
            },
            labels: {
                style: {
                    colors: '#6c757d',
                    fontSize: '13px'
                }
            }
        },
        grid: {
            borderColor: 'rgba(0, 0, 0, 0.08)',
            strokeDashArray: 4,
            xaxis: {
                lines: {
                    show: false
                }
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function(value) {
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
            colors: ['#4a74ff'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 7
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#weeklyProgressChart"), options);
    chart.render();
});
</script>

<?php include_once "includes/footer.php"; ?>
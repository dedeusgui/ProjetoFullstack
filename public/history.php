<?php 
$showRegisterButton = false;
$hideLoginButton = true;

// TODO: Backend - Substituir por dados reais do banco de dados
// Dados mockados para demonstração
$userData = [
    'name' => 'João Silva',
    'email' => 'joao.silva@email.com',
    'initials' => 'JS'
];

$stats = [
    'total_habits' => 8,
    'total_completions' => 156,
    'current_streak' => 12,
    'best_streak' => 20,
    'completion_rate' => 85,
    'active_days' => 23,
    'total_days' => 30
];

// Dados para gráfico mensal (últimos 30 dias)
$monthlyData = [
    'labels' => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30'],
    'completed' => [5, 6, 7, 5, 8, 6, 7, 8, 6, 5, 7, 8, 6, 7, 5, 6, 8, 7, 6, 5, 7, 8, 6, 5, 7, 6, 8, 7, 6, 5],
    'total' => [8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8]
];

// Dados por categoria
$categoryStats = [
    ['category' => 'Saúde Física', 'total' => 45, 'percentage' => 28.8],
    ['category' => 'Saúde Mental', 'total' => 38, 'percentage' => 24.4],
    ['category' => 'Desenvolvimento Pessoal', 'total' => 32, 'percentage' => 20.5],
    ['category' => 'Estudo', 'total' => 25, 'percentage' => 16.0],
    ['category' => 'Trabalho', 'total' => 16, 'percentage' => 10.3]
];

// Conquistas
$achievements = [
    [
        'id' => 1,
        'name' => 'Primeira Semana',
        'description' => 'Complete 7 dias consecutivos',
        'icon' => 'bi-trophy',
        'unlocked' => true,
        'date' => '2024-01-22'
    ],
    [
        'id' => 2,
        'name' => 'Consistência',
        'description' => 'Mantenha 30 dias de streak',
        'icon' => 'bi-fire',
        'unlocked' => false,
        'progress' => 40
    ],
    [
        'id' => 3,
        'name' => 'Centurião',
        'description' => 'Complete 100 hábitos',
        'icon' => 'bi-award',
        'unlocked' => true,
        'date' => '2024-02-05'
    ],
    [
        'id' => 4,
        'name' => 'Perfeccionista',
        'description' => 'Atinja 100% em um dia',
        'icon' => 'bi-star',
        'unlocked' => true,
        'date' => '2024-01-28'
    ],
    [
        'id' => 5,
        'name' => 'Maratonista',
        'description' => 'Complete 500 hábitos',
        'icon' => 'bi-gem',
        'unlocked' => false,
        'progress' => 31
    ]
];

// Histórico recente (últimos 10 dias)
$recentHistory = [
    ['date' => '2024-02-10', 'completed' => 5, 'total' => 8, 'percentage' => 62.5],
    ['date' => '2024-02-09', 'completed' => 6, 'total' => 8, 'percentage' => 75.0],
    ['date' => '2024-02-08', 'completed' => 7, 'total' => 8, 'percentage' => 87.5],
    ['date' => '2024-02-07', 'completed' => 6, 'total' => 8, 'percentage' => 75.0],
    ['date' => '2024-02-06', 'completed' => 8, 'total' => 8, 'percentage' => 100.0],
    ['date' => '2024-02-05', 'completed' => 7, 'total' => 8, 'percentage' => 87.5],
    ['date' => '2024-02-04', 'completed' => 6, 'total' => 8, 'percentage' => 75.0],
    ['date' => '2024-02-03', 'completed' => 5, 'total' => 8, 'percentage' => 62.5],
    ['date' => '2024-02-02', 'completed' => 7, 'total' => 8, 'percentage' => 87.5],
    ['date' => '2024-02-01', 'completed' => 8, 'total' => 8, 'percentage' => 100.0]
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
                        <a href="dashboard.php" class="nav-link">
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
                        <a href="history.php" class="nav-link active">
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
                        <a href="index.php" class="nav-link">
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
        <div class="dashboard-header" style="margin-bottom: var(--space-lg);">
            <div class="d-flex justify-content-between align-items-center" style="flex-wrap: wrap; gap: var(--space-md);">
                <div>
                    <h1 class="dashboard-title">Histórico e Estatísticas 📊</h1>
                    <p class="dashboard-subtitle">Acompanhe sua evolução e conquistas</p>
                </div>
                <button class="doitly-btn doitly-btn-secondary" onclick="exportData()">
                    <i class="bi bi-download"></i>
                    Exportar Dados
                </button>
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="quick-stats" style="margin-bottom: var(--space-xl);">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Concluído</span>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['total_completions']; ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>hábitos completados</span>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-header">
                    <span class="stat-label">Taxa de Sucesso</span>
                    <div class="stat-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['completion_rate']; ?>%</h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>média mensal</span>
                </div>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-header">
                    <span class="stat-label">Sequência Atual</span>
                    <div class="stat-icon">
                        <i class="bi bi-fire"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['current_streak']; ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>dias consecutivos</span>
                </div>
            </div>

            <div class="stat-card stat-danger">
                <div class="stat-header">
                    <span class="stat-label">Melhor Sequência</span>
                    <div class="stat-icon">
                        <i class="bi bi-star"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['best_streak']; ?></h2>
                <div class="stat-change neutral">
                    <i class="bi bi-dash"></i>
                    <span>recorde pessoal</span>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="dashboard-grid">
            <!-- Monthly Progress Chart -->
            <div class="grid-col-8">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-calendar-range"></i>
                            Progresso Mensal
                        </h3>
                        <div class="card-actions">
                            <select class="doitly-input" style="width: auto; padding: 8px 12px; font-size: 0.875rem;">
                                <option>Últimos 30 dias</option>
                                <option>Últimos 60 dias</option>
                                <option>Últimos 90 dias</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="monthlyChart"></div>
                    </div>
                </div>
            </div>

            <!-- Category Distribution -->
            <div class="grid-col-4">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-pie-chart"></i>
                            Por Categoria
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="categoryChart"></div>
                    </div>
                </div>
            </div>

            <!-- Completion Rate Trend -->
            <div class="grid-col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-graph-up"></i>
                            Taxa de Conclusão - Últimos 30 Dias
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="completionRateChart"></div>
                    </div>
                </div>
            </div>

            <!-- Achievements -->
            <div class="grid-col-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-award"></i>
                            Conquistas
                        </h3>
                        <div class="card-actions">
                            <span class="doitly-badge doitly-badge-success">
                                <?php echo count(array_filter($achievements, fn($a) => $a['unlocked'])); ?>/<?php echo count($achievements); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-md">
                            <?php foreach ($achievements as $achievement): ?>
                                <div style="display: flex; align-items: center; gap: var(--space-md); padding: var(--space-md); background: var(--glass-bg-light); border-radius: var(--radius-medium); border: var(--border-light); <?php echo !$achievement['unlocked'] ? 'opacity: 0.6;' : ''; ?>">
                                    <div style="width: 56px; height: 56px; background: <?php echo $achievement['unlocked'] ? 'linear-gradient(135deg, var(--accent-blue), var(--accent-green))' : 'var(--glass-bg-medium)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--text-white); flex-shrink: 0;">
                                        <i class="<?php echo $achievement['icon']; ?>"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <h4 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: var(--font-semibold);">
                                            <?php echo $achievement['name']; ?>
                                            <?php if ($achievement['unlocked']): ?>
                                                <i class="bi bi-check-circle-fill" style="color: var(--accent-green); font-size: 0.875rem;"></i>
                                            <?php endif; ?>
                                        </h4>
                                        <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">
                                            <?php echo $achievement['description']; ?>
                                        </p>
                                        <?php if (!$achievement['unlocked'] && isset($achievement['progress'])): ?>
                                            <div style="margin-top: 8px;">
                                                <div style="height: 6px; background: var(--glass-bg-medium); border-radius: var(--radius-pill); overflow: hidden;">
                                                    <div style="height: 100%; width: <?php echo $achievement['progress']; ?>%; background: linear-gradient(90deg, var(--accent-blue), var(--accent-green)); transition: width 0.3s ease;"></div>
                                                </div>
                                                <small style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 4px; display: block;">
                                                    <?php echo $achievement['progress']; ?>% completo
                                                </small>
                                            </div>
                                        <?php elseif ($achievement['unlocked']): ?>
                                            <small style="font-size: 0.75rem; color: var(--accent-green); margin-top: 4px; display: block;">
                                                <i class="bi bi-calendar-check"></i> Desbloqueado em <?php echo date('d/m/Y', strtotime($achievement['date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent History -->
            <div class="grid-col-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-clock-history"></i>
                            Histórico Recente
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-sm">
                            <?php foreach ($recentHistory as $day): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-sm) var(--space-md); background: var(--glass-bg-light); border-radius: var(--radius-small); border-left: 4px solid <?php echo $day['percentage'] >= 80 ? 'var(--accent-green)' : ($day['percentage'] >= 50 ? 'var(--accent-gold)' : 'var(--accent-red)'); ?>;">
                                    <div>
                                        <div style="font-size: 0.875rem; font-weight: var(--font-medium); color: var(--text-primary);">
                                            <?php 
                                            $date = new DateTime($day['date']);
                                            echo $date->format('d/m/Y');
                                            ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                            <?php echo $day['completed']; ?>/<?php echo $day['total']; ?> hábitos
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.125rem; font-weight: var(--font-semibold); color: <?php echo $day['percentage'] >= 80 ? 'var(--accent-green)' : ($day['percentage'] >= 50 ? 'var(--accent-gold)' : 'var(--accent-red)'); ?>;">
                                            <?php echo number_format($day['percentage'], 0); ?>%
                                        </div>
                                        <?php if ($day['percentage'] == 100): ?>
                                            <div style="font-size: 0.75rem; color: var(--accent-green);">
                                                <i class="bi bi-star-fill"></i> Perfeito!
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Stats Table -->
            <div class="grid-col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-bar-chart"></i>
                            Estatísticas por Categoria
                        </h3>
                    </div>
                    <div class="card-body">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: var(--border-light);">
                                        <th style="padding: var(--space-md); text-align: left; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">Categoria</th>
                                        <th style="padding: var(--space-md); text-align: center; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">Total Concluído</th>
                                        <th style="padding: var(--space-md); text-align: center; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">Porcentagem</th>
                                        <th style="padding: var(--space-md); text-align: right; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">Progresso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryStats as $cat): ?>
                                        <tr style="border-bottom: var(--border-light);">
                                            <td style="padding: var(--space-md); font-weight: var(--font-medium);">
                                                <?php echo $cat['category']; ?>
                                            </td>
                                            <td style="padding: var(--space-md); text-align: center; font-weight: var(--font-semibold); color: var(--accent-blue);">
                                                <?php echo $cat['total']; ?>
                                            </td>
                                            <td style="padding: var(--space-md); text-align: center;">
                                                <?php echo number_format($cat['percentage'], 1); ?>%
                                            </td>
                                            <td style="padding: var(--space-md);">
                                                <div style="display: flex; align-items: center; gap: var(--space-sm); justify-content: flex-end;">
                                                    <div style="flex: 1; max-width: 200px; height: 8px; background: var(--glass-bg-medium); border-radius: var(--radius-pill); overflow: hidden;">
                                                        <div style="height: 100%; width: <?php echo $cat['percentage']; ?>%; background: linear-gradient(90deg, var(--accent-blue), var(--accent-green)); transition: width 0.3s ease;"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Charts Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // TODO: Backend - Substituir por dados reais da API
    
    // Monthly Progress Chart
    var monthlyOptions = {
        series: [{
            name: 'Concluídos',
            data: <?php echo json_encode($monthlyData['completed']); ?>
        }, {
            name: 'Total',
            data: <?php echo json_encode($monthlyData['total']); ?>
        }],
        chart: {
            type: 'line',
            height: 350,
            fontFamily: 'Inter, sans-serif',
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false
                }
            }
        },
        colors: ['#4a74ff', '#e6e7e9'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: [3, 2]
        },
        xaxis: {
            categories: <?php echo json_encode($monthlyData['labels']); ?>,
            labels: {
                style: {
                    colors: '#6c757d',
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Hábitos',
                style: {
                    color: '#6c757d',
                    fontSize: '13px'
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        },
        grid: {
            borderColor: 'rgba(0, 0, 0, 0.08)',
            strokeDashArray: 4
        }
    };
    
    var monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), monthlyOptions);
    monthlyChart.render();
    
    // Category Distribution Chart
    var categoryOptions = {
        series: <?php echo json_encode(array_column($categoryStats, 'total')); ?>,
        chart: {
            type: 'donut',
            height: 320,
            fontFamily: 'Inter, sans-serif'
        },
        labels: <?php echo json_encode(array_column($categoryStats, 'category')); ?>,
        colors: ['#4a74ff', '#59d186', '#eed27a', '#ff5757', '#a78bfa'],
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return Math.round(val) + '%';
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '16px',
                            fontWeight: 600,
                            color: '#222222'
                        }
                    }
                }
            }
        }
    };
    
    var categoryChart = new ApexCharts(document.querySelector("#categoryChart"), categoryOptions);
    categoryChart.render();
    
    // Completion Rate Chart
    var completionRateOptions = {
        series: [{
            name: 'Taxa de Conclusão',
            data: [62.5, 75, 87.5, 75, 100, 87.5, 75, 62.5, 87.5, 100, 75, 87.5, 62.5, 75, 100, 87.5, 75, 62.5, 87.5, 100, 75, 87.5, 75, 62.5, 87.5, 75, 100, 87.5, 75, 62.5]
        }],
        chart: {
            type: 'area',
            height: 280,
            fontFamily: 'Inter, sans-serif',
            toolbar: {
                show: false
            }
        },
        colors: ['#59d186'],
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
                opacityTo: 0.1
            }
        },
        xaxis: {
            categories: <?php echo json_encode($monthlyData['labels']); ?>,
            labels: {
                style: {
                    colors: '#6c757d',
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Porcentagem (%)',
                style: {
                    color: '#6c757d',
                    fontSize: '13px'
                }
            },
            min: 0,
            max: 100
        },
        grid: {
            borderColor: 'rgba(0, 0, 0, 0.08)',
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value.toFixed(1) + '%';
                }
            }
        }
    };
    
    var completionRateChart = new ApexCharts(document.querySelector("#completionRateChart"), completionRateOptions);
    completionRateChart.render();
});

function exportData() {
    // TODO: Backend - Implementar exportação real (CSV/PDF)
    alert('📥 Exportando dados...\n\n(TODO: Backend - Implementar exportação em CSV ou PDF)');
}
</script>

<style>
table tbody tr:hover {
    background: var(--glass-bg-light);
}

@media (max-width: 768px) {
    table {
        font-size: 0.875rem;
    }
    
    table th,
    table td {
        padding: var(--space-sm) !important;
    }
}
</style>

<?php include_once "includes/footer.php"; ?>
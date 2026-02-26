<?php
// Proteger página - requer login
require_once '../config/bootstrap.php';

use App\Api\Internal\StatsApiPayloadBuilder;
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

// Carregar dados centralizados pela API (somente via PHP interno)
$historyPayload = StatsApiPayloadBuilder::build($conn, (int) $userId, 'history');
$historyData = $historyPayload['data'] ?? [];

$stats = $historyData['stats'] ?? [];
$monthlyData = $historyData['monthly_data'] ?? ['labels' => [], 'completed' => [], 'total' => []];

$completionRateSeries = [];
$monthlyCompleted = $monthlyData['completed'] ?? [];
$monthlyTotal = $monthlyData['total'] ?? [];

foreach ($monthlyCompleted as $index => $completedValue) {
    $totalValue = $monthlyTotal[$index] ?? 0;
    $completionRateSeries[] = $totalValue > 0 ? round(($completedValue / $totalValue) * 100, 1) : 0;
}
$categoryStats = $historyData['category_stats'] ?? [];
$achievements = $historyData['achievements'] ?? [];
$recentHistory = $historyData['recent_history'] ?? [];

$tierOrder = ['bronze' => 1, 'prata' => 2, 'ouro' => 3];
$categoryOrder = ['consistencia' => 1, 'exploracao' => 2, 'performance' => 3, 'social' => 4];

usort($achievements, static function (array $a, array $b) use ($tierOrder, $categoryOrder): int {
    $categoryCompare = ($categoryOrder[$a['category'] ?? 'performance'] ?? 99) <=> ($categoryOrder[$b['category'] ?? 'performance'] ?? 99);
    if ($categoryCompare !== 0) {
        return $categoryCompare;
    }

    $tierCompare = ($tierOrder[$a['tier'] ?? 'bronze'] ?? 99) <=> ($tierOrder[$b['tier'] ?? 'bronze'] ?? 99);
    if ($tierCompare !== 0) {
        return $tierCompare;
    }

    return ($a['criteria_value'] ?? 0) <=> ($b['criteria_value'] ?? 0);
});

$groupedAchievements = [];
foreach ($achievements as $achievement) {
    $category = $achievement['category'] ?? 'performance';
    $groupedAchievements[$category][] = $achievement;
}

$userProgressService = new UserProgressService($conn);
$profileSummary = $userProgressService->refreshUserProgressSummary((int) $userId, $achievements);
$userData['level'] = (int) ($profileSummary['level'] ?? 1);
$unlockedAchievementsCount = (int) ($profileSummary['unlocked_achievements_count'] ?? 0);

$achievementCount = (int) ($profileSummary['achievements_count'] ?? count($achievements));
$unlockedAchievements = $profileSummary['unlocked_achievements'] ?? [];
$unlockedCount = (int) ($profileSummary['unlocked_achievements_count'] ?? count($unlockedAchievements));

$totalXp = (int) ($profileSummary['total_xp'] ?? 0);
$currentLevel = (int) ($profileSummary['level'] ?? 1);
$xpIntoCurrentLevel = (int) ($profileSummary['xp_into_level'] ?? 0);
$xpNeededForLevel = (int) ($profileSummary['xp_needed_for_level'] ?? 1);
$xpToNextLevel = (int) ($profileSummary['xp_to_next_level'] ?? 0);
$xpLevelProgress = (int) ($profileSummary['xp_progress_percent'] ?? 0);

$recentRewards = $unlockedAchievements;
usort($recentRewards, static function (array $a, array $b): int {
    return strtotime((string) ($b['date'] ?? '1970-01-01')) <=> strtotime((string) ($a['date'] ?? '1970-01-01'));
});
$recentRewards = array_slice($recentRewards, 0, 5);

$justUnlocked = array_values(array_filter($achievements, static fn(array $achievement): bool => (bool) ($achievement['just_unlocked'] ?? false)));

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
                    <li class="nav-item">
                        <a href="achievements.php" class="nav-link">
                            <i class="bi bi-trophy"></i>
                            <span>Conquistas</span>
                            <span class="nav-badge"><?php echo $unlockedAchievementsCount; ?></span>
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
        <!-- Header -->
        <div class="dashboard-header" style="margin-bottom: var(--space-lg);">
            <div class="d-flex justify-content-between align-items-center"
                style="flex-wrap: wrap; gap: var(--space-md);">
                <div>
                    <h1 class="dashboard-title">Histórico e estatísticas 📊</h1>
                    <p class="dashboard-subtitle">Acompanhe sua evolução e conquistas</p>
                </div>
                <a href="../actions/export_user_data_csv_action.php" class="doitly-btn doitly-btn-secondary">
                    <i class="bi bi-download"></i>
                    Exportar dados
                </a>
            </div>
        </div>

        <?php if (!empty($justUnlocked)): ?>
            <div class="dashboard-card" style="margin-bottom: var(--space-lg); border: 1px solid rgba(89, 209, 134, 0.35);">
                <div class="card-body" style="display:flex; align-items:center; gap: var(--space-sm);">
                    <i class="bi bi-stars" style="color: var(--accent-green); font-size: 1.4rem;"></i>
                    <div>
                        <strong>Nova conquista desbloqueada!</strong>
                        <span style="color: var(--text-secondary);">Você acabou de ganhar: <?php echo htmlspecialchars($justUnlocked[0]['name'], ENT_QUOTES, 'UTF-8'); ?>.</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="dashboard-card" style="margin-bottom: var(--space-xl);">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-lightning-charge"></i>
                    Progressão e recompensas
                </h3>
                <span class="doitly-badge doitly-badge-warning">Nível <?php echo $currentLevel; ?></span>
            </div>
            <div class="card-body history-progress-rewards-grid" style="display:grid; grid-template-columns: 2fr 1fr; gap: var(--space-lg);">
                <div>
                    <p style="margin-bottom: 8px; color: var(--text-secondary);">
                        XP total: <strong style="color: var(--text-primary);"><?php echo $totalXp; ?></strong> ·
                        Faltam <strong style="color: var(--text-primary);"><?php echo $xpToNextLevel; ?> XP</strong> para o próximo nível
                    </p>
                    <div style="height: 10px; background: var(--glass-bg-medium); border-radius: var(--radius-pill); overflow: hidden;">
                        <div style="height: 100%; width: <?php echo $xpLevelProgress; ?>%; background: linear-gradient(90deg, var(--accent-gold), var(--accent-blue));"></div>
                    </div>
                    <small style="display:block; margin-top: 6px; color: var(--text-tertiary);"><?php echo $xpIntoCurrentLevel; ?>/<?php echo $xpNeededForLevel; ?> XP no nível atual</small>
                </div>
                <div>
                    <p style="margin: 0 0 8px 0; font-weight: var(--font-semibold);">Últimas recompensas</p>
                    <div class="d-flex flex-column gap-sm">
                        <?php if (empty($recentRewards)): ?>
                            <small style="color: var(--text-tertiary);">Conclua suas primeiras conquistas para ganhar XP.</small>
                        <?php else: ?>
                            <?php foreach ($recentRewards as $reward): ?>
                                <small class="history-reward-row" style="display:flex; justify-content:space-between; gap:8px;">
                                    <span><?php echo htmlspecialchars($reward['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span style="color: var(--accent-gold);">+<?php echo (int) ($reward['points'] ?? 0); ?> XP</span>
                                </small>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="quick-stats" style="margin-bottom: var(--space-xl);">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total concluído</span>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['total_completions']; ?></h2>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>hábitos concluídos</span>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-header">
                    <span class="stat-label">Taxa de sucesso</span>
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
                    <span class="stat-label">Sequência atual</span>
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
                    <span class="stat-label">Melhor sequência</span>
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
                            Progresso mensal
                        </h3>
                        <div class="card-actions">
                            <select class="doitly-input history-range-select" style="width: auto; padding: 8px 12px; font-size: 0.875rem;">
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
                            Por categoria
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
                            Taxa de conclusão - últimos 30 dias
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="completionRateChart"></div>
                    </div>
                </div>
            </div>

            <!-- Achievements -->
            <div class="grid-col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-award"></i>
                            Conquistas
                        </h3>
                        <div class="card-actions" style="display:flex; align-items:center; gap: var(--space-sm);">
                            <span class="doitly-badge doitly-badge-success">
                                <?php echo $unlockedCount; ?>/<?php echo $achievementCount; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body history-achievements-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="d-flex flex-column gap-md">
                            <?php foreach ($groupedAchievements as $category => $categoryAchievements): ?>
                                <div>
                                    <h4 style="margin: 0 0 var(--space-sm) 0; text-transform: capitalize; font-size: 0.85rem; letter-spacing: 0.04em; color: var(--text-tertiary);">
                                        <?php echo htmlspecialchars(str_replace('_', ' ', $category), ENT_QUOTES, 'UTF-8'); ?>
                                    </h4>
                                    <div class="d-flex flex-column gap-sm">
                                        <?php foreach ($categoryAchievements as $achievement): ?>
                                            <div
                                                style="display: flex; align-items: center; gap: var(--space-md); padding: var(--space-md); background: var(--glass-bg-light); border-radius: var(--radius-medium); border: var(--border-light); <?php echo !$achievement['unlocked'] ? 'opacity: 0.82;' : ''; ?>">
                                                <div
                                                    style="width: 56px; height: 56px; background: <?php echo $achievement['unlocked'] ? 'linear-gradient(135deg, var(--accent-blue), var(--accent-green))' : 'var(--glass-bg-medium)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: <?php echo $achievement['unlocked'] ? 'var(--text-white)' : 'var(--text-primary)'; ?>; flex-shrink: 0;">
                                                    <i class="<?php echo htmlspecialchars($achievement['icon'] ?? 'bi bi-patch-check-fill', ENT_QUOTES, 'UTF-8'); ?>"></i>
                                                </div>
                                                <div style="flex: 1; min-width: 0;">
                                                    <h4 style="margin: 0 0 4px 0; font-size: 1rem; font-weight: var(--font-semibold);">
                                                        <?php echo htmlspecialchars($achievement['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        <span style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-tertiary); margin-left: 4px;">
                                                            <?php echo htmlspecialchars($achievement['tier'] ?? 'bronze', ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                        <?php if ($achievement['unlocked']): ?>
                                                            <i class="bi bi-check-circle-fill"
                                                                style="color: var(--accent-green); font-size: 0.875rem;"></i>
                                                        <?php endif; ?>
                                                    </h4>
                                                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">
                                                        <?php echo htmlspecialchars($achievement['description'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </p>
                                                    <?php if (!$achievement['unlocked']): ?>
                                                        <div style="margin-top: 8px;">
                                                            <div
                                                                style="height: 6px; background: var(--glass-bg-medium); border-radius: var(--radius-pill); overflow: hidden;">
                                                                <div
                                                                    style="height: 100%; width: <?php echo (int) ($achievement['progress_percent'] ?? 0); ?>%; background: linear-gradient(90deg, var(--accent-blue), var(--accent-green)); transition: width 0.3s ease;">
                                                                </div>
                                                            </div>
                                                            <small
                                                                style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 4px; display: block;">
                                                                <?php echo htmlspecialchars($achievement['progress_label'] ?? '0/0', ENT_QUOTES, 'UTF-8'); ?> · <?php echo (int) ($achievement['progress_percent'] ?? 0); ?>% completo
                                                                <?php if (!empty($achievement['is_near_completion'])): ?>
                                                                    <span style="color: var(--accent-gold);">· Quase lá! 🚀</span>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <small
                                                            style="font-size: 0.75rem; color: var(--accent-green); margin-top: 4px; display: block;">
                                                            <i class="bi bi-calendar-check"></i> Desbloqueado em
                                                            <?php echo !empty($achievement['date']) ? date('d/m/Y', strtotime($achievement['date'])) : 'hoje'; ?> · +<?php echo (int) ($achievement['points'] ?? 0); ?> XP
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent History -->
            <div class="grid-col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-clock-history"></i>
                            Histórico recente (até 10 dias)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-sm history-recent-list" style="max-height: 260px; overflow-y: auto;">
                            <?php foreach ($recentHistory as $day): ?>
                                <div
                                    style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-sm) var(--space-md); background: var(--glass-bg-light); border-radius: var(--radius-small); border-left: 4px solid <?php echo $day['percentage'] >= 80 ? 'var(--accent-green)' : ($day['percentage'] >= 50 ? 'var(--accent-gold)' : 'var(--accent-red)'); ?>;">
                                    <div>
                                        <div
                                            style="font-size: 0.875rem; font-weight: var(--font-medium); color: var(--text-primary);">
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
                                        <div
                                            style="font-size: 1.125rem; font-weight: var(--font-semibold); color: <?php echo $day['percentage'] >= 80 ? 'var(--accent-green)' : ($day['percentage'] >= 50 ? 'var(--accent-gold)' : 'var(--accent-red)'); ?>;">
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
                            Estatísticas por categoria
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="history-table-wrap" style="overflow-x: auto;">
                            <table class="history-category-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: var(--border-light);">
                                        <th
                                            style="padding: var(--space-md); text-align: left; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">
                                            Categoria</th>
                                        <th
                                            style="padding: var(--space-md); text-align: center; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">
                                            Total concluído</th>
                                        <th
                                            style="padding: var(--space-md); text-align: center; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">
                                            Porcentagem</th>
                                        <th
                                            style="padding: var(--space-md); text-align: right; font-size: 0.875rem; font-weight: var(--font-semibold); color: var(--text-secondary);">
                                            Progresso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryStats as $cat): ?>
                                        <tr style="border-bottom: var(--border-light);">
                                            <td style="padding: var(--space-md); font-weight: var(--font-medium);">
                                                <?php echo $cat['category']; ?>
                                            </td>
                                            <td
                                                style="padding: var(--space-md); text-align: center; font-weight: var(--font-semibold); color: var(--accent-blue);">
                                                <?php echo $cat['total']; ?>
                                            </td>
                                            <td style="padding: var(--space-md); text-align: center;">
                                                <?php echo number_format($cat['percentage'], 1); ?>%
                                            </td>
                                            <td style="padding: var(--space-md);">
                                                <div
                                                    style="display: flex; align-items: center; gap: var(--space-sm); justify-content: flex-end;">
                                                    <div
                                                        style="flex: 1; max-width: 200px; height: 8px; background: var(--glass-bg-medium); border-radius: var(--radius-pill); overflow: hidden;">
                                                        <div
                                                            style="height: 100%; width: <?php echo $cat['percentage']; ?>%; background: linear-gradient(90deg, var(--accent-blue), var(--accent-green)); transition: width 0.3s ease;">
                                                        </div>
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

<?php include_once "includes/settings_modal.php"; ?>
<?php include_once "includes/profile_modal.php"; ?>

<!-- Charts Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const getThemeChartOptions = () => {
            const styles = getComputedStyle(document.documentElement);
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                accentBlue: styles.getPropertyValue('--accent-blue').trim() || '#4a74ff',
                accentGreen: styles.getPropertyValue('--accent-green').trim() || '#59d186',
                accentGold: styles.getPropertyValue('--accent-gold').trim() || '#eed27a',
                accentRed: styles.getPropertyValue('--accent-red').trim() || '#ff5757',
                accentPurple: styles.getPropertyValue('--accent-purple').trim() || '#a78bfa',
                bgDarker: styles.getPropertyValue('--bg-darker').trim() || '#e6e7e9',
                textPrimary: styles.getPropertyValue('--text-primary').trim() || '#222222',
                textSecondary: styles.getPropertyValue('--text-secondary').trim() || '#6c757d',
                border: isDark ? 'rgba(255,255,255,0.18)' : 'rgba(0, 0, 0, 0.08)',
                tooltipTheme: isDark ? 'dark' : 'light'
            };
        };

        const buildMonthlyOptions = () => {
            const theme = getThemeChartOptions();
            return {
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
            colors: [theme.accentBlue, theme.bgDarker],
            noData: {
                text: 'Sem dados suficientes para o período'
            },
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
                        colors: theme.textSecondary,
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Hábitos',
                    style: {
                        color: theme.textSecondary,
                        fontSize: '13px'
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            grid: {
                borderColor: theme.border,
                strokeDashArray: 4
            }
        };
        };

        const buildCategoryOptions = () => {
            const theme = getThemeChartOptions();
            return {
            series: <?php echo json_encode(array_column($categoryStats, 'total')); ?>,
            chart: {
                type: 'donut',
                height: 320,
                fontFamily: 'Inter, sans-serif'
            },
            labels: <?php echo json_encode(array_column($categoryStats, 'category')); ?>,
            noData: {
                text: 'Sem dados de categorias ainda'
            },
            colors: [theme.accentBlue, theme.accentGreen, theme.accentGold, theme.accentRed, theme.accentPurple],
            legend: {
                position: 'bottom'
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
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
                                color: theme.textPrimary
                            }
                        }
                    }
                }
            },
            tooltip: {
                theme: theme.tooltipTheme
            }
        };
        };

        const buildCompletionRateOptions = () => {
            const theme = getThemeChartOptions();
            return {
            series: [{
                name: 'Taxa de Conclusão',
                data: <?php echo json_encode($completionRateSeries); ?>
            }],
            chart: {
                type: 'area',
                height: 280,
                fontFamily: 'Inter, sans-serif',
                toolbar: {
                    show: false
                }
            },
            colors: [theme.accentGreen],
            noData: {
                text: 'Sem dados suficientes para o período'
            },
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
                        colors: theme.textSecondary,
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Porcentagem (%)',
                    style: {
                        color: theme.textSecondary,
                        fontSize: '13px'
                    }
                },
                min: 0,
                max: 100
            },
            grid: {
                borderColor: theme.border,
                strokeDashArray: 4
            },
            tooltip: {
                theme: theme.tooltipTheme,
                y: {
                    formatter: function (value) {
                        return value.toFixed(1) + '%';
                    }
                }
            }
        };
        };

        let monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), buildMonthlyOptions());
        monthlyChart.render();

        let categoryChart = new ApexCharts(document.querySelector("#categoryChart"), buildCategoryOptions());
        categoryChart.render();

        let completionRateChart = new ApexCharts(document.querySelector("#completionRateChart"), buildCompletionRateOptions());
        completionRateChart.render();

        window.addEventListener('doitly:theme-change', () => {
            monthlyChart.destroy();
            categoryChart.destroy();
            completionRateChart.destroy();

            monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), buildMonthlyOptions());
            categoryChart = new ApexCharts(document.querySelector("#categoryChart"), buildCategoryOptions());
            completionRateChart = new ApexCharts(document.querySelector("#completionRateChart"), buildCompletionRateOptions());

            monthlyChart.render();
            categoryChart.render();
            completionRateChart.render();
        });
    });

</script>

<style>
    table tbody tr:hover {
        background: var(--glass-bg-light);
    }

    .history-progress-rewards-grid {
        align-items: start;
    }

    .history-reward-row {
        align-items: center;
    }

    .history-table-wrap {
        padding-bottom: 2px;
    }

    @media (max-width: 768px) {
        .history-progress-rewards-grid {
            grid-template-columns: 1fr !important;
            gap: var(--space-md) !important;
        }

        .history-reward-row {
            flex-wrap: wrap;
            justify-content: flex-start !important;
        }

        .history-range-select {
            width: 100% !important;
        }

        .history-achievements-body {
            max-height: none !important;
        }

        .history-recent-list {
            max-height: none !important;
        }

        .history-category-table {
            min-width: 540px;
        }

        table {
            font-size: 0.875rem;
        }

        table th,
        table td {
            padding: var(--space-sm) !important;
        }
    }

    @media (max-width: 480px) {
        .history-category-table {
            min-width: 500px;
        }
    }
</style>

<?php include_once "includes/footer.php"; ?>

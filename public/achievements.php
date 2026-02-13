<?php
require_once '../config/conexao.php';
require_once '../config/auth.php';
require_once '../config/helpers.php';

requireLogin();

$showRegisterButton = false;
$hideLoginButton = true;

$userId = getUserId();
$userData = getCurrentUser($conn);

if (!$userData) {
    logout();
}

$userData['initials'] = getInitials($userData['name']);

$stats = [
    'total_habits' => getTotalHabits($conn, $userId)
];

$achievements = getUserAchievements($conn, (int) $userId);

$rarityMap = [
    'common' => ['label' => 'Comum', 'class' => 'rarity-common'],
    'rare' => ['label' => 'Rara', 'class' => 'rarity-rare'],
    'epic' => ['label' => 'Épica', 'class' => 'rarity-epic'],
    'legendary' => ['label' => 'Lendária', 'class' => 'rarity-legendary']
];

$selectedRarity = $_GET['rarity'] ?? 'all';
$selectedSort = $_GET['sort'] ?? 'progress_desc';

$filteredAchievements = array_values(array_filter($achievements, static function (array $achievement) use ($selectedRarity): bool {
    if ($selectedRarity === 'all') {
        return true;
    }

    return ($achievement['rarity'] ?? '') === $selectedRarity;
}));

usort($filteredAchievements, static function (array $first, array $second) use ($selectedSort): int {
    switch ($selectedSort) {
        case 'points_desc':
            return $second['points'] <=> $first['points'];
        case 'points_asc':
            return $first['points'] <=> $second['points'];
        case 'progress_asc':
            return $first['progress'] <=> $second['progress'];
        case 'unlocked_first':
            $unlockedComparison = ((int) $second['unlocked']) <=> ((int) $first['unlocked']);
            if ($unlockedComparison !== 0) {
                return $unlockedComparison;
            }
            return $second['progress'] <=> $first['progress'];
        case 'progress_desc':
        default:
            return $second['progress'] <=> $first['progress'];
    }
});

$unlockedCount = count(array_filter($achievements, static fn(array $achievement): bool => (bool) $achievement['unlocked']));
$totalPoints = array_reduce($achievements, static function (int $carry, array $achievement): int {
    return $carry + ($achievement['unlocked'] ? (int) $achievement['points'] : 0);
}, 0);

include_once 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/dashboard.css" />
<link rel="stylesheet" href="assets/css/achievements.css" />

<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar">
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
                    <h4 class="user-name"><?php echo htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                    <p class="user-email"><?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>

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
                            <span class="nav-badge"><?php echo (int) $stats['total_habits']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="history.php" class="nav-link">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Histórico</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="achievements.php" class="nav-link active">
                            <i class="bi bi-award"></i>
                            <span>Conquistas</span>
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

    <main class="dashboard-content">
        <div class="dashboard-header achievements-header">
            <div>
                <h1 class="dashboard-title">Conquistas e Progressão 🏆</h1>
                <p class="dashboard-subtitle">Acompanhe sua evolução e desbloqueie novos marcos.</p>
            </div>
        </div>

        <div class="quick-stats achievements-stats">
            <div class="stat-card stat-success">
                <div class="stat-header">
                    <span class="stat-label">Desbloqueadas</span>
                    <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
                </div>
                <h2 class="stat-value"><?php echo $unlockedCount; ?>/<?php echo count($achievements); ?></h2>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-header">
                    <span class="stat-label">Pontos Totais</span>
                    <div class="stat-icon"><i class="bi bi-stars"></i></div>
                </div>
                <h2 class="stat-value"><?php echo $totalPoints; ?></h2>
            </div>
        </div>

        <div class="dashboard-card" style="margin-bottom: var(--space-lg);">
            <div class="card-body">
                <form class="achievements-filters" method="get" action="achievements.php">
                    <div>
                        <label for="rarity" class="filter-label">Filtrar por raridade</label>
                        <select id="rarity" name="rarity" class="doitly-input">
                            <option value="all" <?php echo $selectedRarity === 'all' ? 'selected' : ''; ?>>Todas</option>
                            <?php foreach ($rarityMap as $rarityKey => $rarityInfo): ?>
                                <option value="<?php echo $rarityKey; ?>" <?php echo $selectedRarity === $rarityKey ? 'selected' : ''; ?>>
                                    <?php echo $rarityInfo['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="sort" class="filter-label">Ordenar por</label>
                        <select id="sort" name="sort" class="doitly-input">
                            <option value="progress_desc" <?php echo $selectedSort === 'progress_desc' ? 'selected' : ''; ?>>Maior progresso</option>
                            <option value="progress_asc" <?php echo $selectedSort === 'progress_asc' ? 'selected' : ''; ?>>Menor progresso</option>
                            <option value="points_desc" <?php echo $selectedSort === 'points_desc' ? 'selected' : ''; ?>>Mais pontos</option>
                            <option value="points_asc" <?php echo $selectedSort === 'points_asc' ? 'selected' : ''; ?>>Menos pontos</option>
                            <option value="unlocked_first" <?php echo $selectedSort === 'unlocked_first' ? 'selected' : ''; ?>>Desbloqueadas primeiro</option>
                        </select>
                    </div>

                    <button type="submit" class="doitly-btn">
                        <i class="bi bi-funnel"></i>
                        Aplicar
                    </button>
                </form>
            </div>
        </div>

        <?php if (empty($filteredAchievements)): ?>
            <div class="dashboard-card">
                <div class="card-body empty-state">
                    <i class="bi bi-search"></i>
                    <p>Nenhuma conquista encontrada para os filtros selecionados.</p>
                </div>
            </div>
        <?php else: ?>
            <section class="achievements-grid">
                <?php foreach ($filteredAchievements as $achievement): ?>
                    <?php
                    $rarity = $achievement['rarity'] ?? 'common';
                    $rarityInfo = $rarityMap[$rarity] ?? $rarityMap['common'];
                    $progress = max(0, min(100, (int) $achievement['progress']));
                    $isUnlocked = (bool) $achievement['unlocked'];
                    ?>
                    <article class="achievement-card <?php echo $isUnlocked ? 'is-unlocked' : 'is-locked'; ?>"
                        data-tooltip="<?php echo htmlspecialchars($achievement['description'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="achievement-top">
                            <div class="achievement-icon" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($achievement['badge_color'], ENT_QUOTES, 'UTF-8'); ?>, var(--accent-blue));">
                                <i class="bi <?php echo htmlspecialchars($achievement['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                            </div>
                            <div class="achievement-meta">
                                <span class="rarity-badge <?php echo $rarityInfo['class']; ?>"><?php echo $rarityInfo['label']; ?></span>
                                <span class="points-badge"><i class="bi bi-lightning-charge"></i> <?php echo (int) $achievement['points']; ?> pts</span>
                            </div>
                        </div>

                        <h3 class="achievement-title"><?php echo htmlspecialchars($achievement['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="achievement-description"><?php echo htmlspecialchars($achievement['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                        <div class="progress-wrapper" aria-label="Progresso da conquista">
                            <div class="progress-bar-track">
                                <span class="progress-bar-fill" style="--progress-width: <?php echo $progress; ?>%;"></span>
                            </div>
                            <span class="progress-text"><?php echo $progress; ?>%</span>
                        </div>

                        <div class="achievement-footer">
                            <?php if ($isUnlocked): ?>
                                <span class="status status-unlocked"><i class="bi bi-unlock"></i> Desbloqueada</span>
                            <?php else: ?>
                                <span class="status status-locked"><i class="bi bi-lock"></i> Bloqueada</span>
                            <?php endif; ?>

                            <?php if (!empty($achievement['date'])): ?>
                                <small class="unlock-date"><?php echo date('d/m/Y', strtotime($achievement['date'])); ?></small>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php include_once 'includes/footer.php'; ?>

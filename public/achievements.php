<?php
require_once '../config/bootstrap.php';

use App\Api\Internal\AchievementsApiPayloadBuilder;
use App\UserProgress\UserProgressService;

bootApp();
requireAuthenticatedUser();

$showRegisterButton = false;
$hideLoginButton = true;

$userId = getAuthenticatedUserId();
$userData = getAuthenticatedUserRecord($conn);

if (!$userData) {
    signOutUser();
}

$userData['initials'] = getUserInitials($userData['name']);

$payload = AchievementsApiPayloadBuilder::build($conn, (int) $userId);
$pageData = $payload['data'] ?? [];
$hero = $pageData['hero'] ?? [];
$achievements = $pageData['achievements'] ?? [];
$recentUnlocked = $pageData['recent_unlocked'] ?? [];
$stats = $pageData['stats'] ?? [];

$achievementDifficultyOrder = [
    'common' => 1,
    'rare' => 2,
    'epic' => 3,
    'legendary' => 4,
];

usort($achievements, static function (array $a, array $b) use ($achievementDifficultyOrder): int {
    $aDifficulty = $achievementDifficultyOrder[(string) ($a['rarity'] ?? 'common')] ?? 99;
    $bDifficulty = $achievementDifficultyOrder[(string) ($b['rarity'] ?? 'common')] ?? 99;
    if ($aDifficulty !== $bDifficulty) {
        return $aDifficulty <=> $bDifficulty;
    }

    $aTarget = (int) ($a['progress_target'] ?? $a['criteria_value'] ?? PHP_INT_MAX);
    $bTarget = (int) ($b['progress_target'] ?? $b['criteria_value'] ?? PHP_INT_MAX);
    if ($aTarget !== $bTarget) {
        return $aTarget <=> $bTarget;
    }

    $aPoints = (int) ($a['points'] ?? 0);
    $bPoints = (int) ($b['points'] ?? 0);
    if ($aPoints !== $bPoints) {
        return $aPoints <=> $bPoints;
    }

    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$userProgressService = new UserProgressService($conn);
$profileSummary = $userProgressService->refreshUserProgressSummary((int) $userId, $achievements);
$userData['level'] = (int) ($profileSummary['level'] ?? ($hero['level'] ?? 1));

$rarityConfig = [
    'common' => ['label' => 'Comum', 'color' => '#95a5a6', 'glow' => 'rgba(149,165,166,0.28)'],
    'rare' => ['label' => 'Raro', 'color' => '#3498db', 'glow' => 'rgba(52,152,219,0.34)'],
    'epic' => ['label' => 'Épico', 'color' => '#9b59b6', 'glow' => 'rgba(155,89,182,0.36)'],
    'legendary' => ['label' => 'Lendário', 'color' => '#FFD700', 'glow' => 'rgba(255,215,0,0.4)'],
];

$unlockedCount = (int) ($hero['unlocked_count'] ?? 0);
$habitCount = (int) ($stats['total_habits'] ?? 0);
$totalAvailable = (int) ($hero['total_available'] ?? 0);

$filterLabels = [
    'all' => 'Todas',
    'unlocked' => 'Desbloqueadas',
    'in_progress' => 'Em progresso',
    'locked' => 'Bloqueadas',
    'common' => 'Comum',
    'rare' => 'Raro',
    'epic' => 'Épico',
    'legendary' => 'Lendário',
];

include_once 'includes/header.php';
?>

<?php include __DIR__ . '/includes/partials/dashboard_head_assets.php'; ?>
<link rel="stylesheet" href="assets/css/achievements.css?v=<?php echo filemtime(__DIR__ . '/assets/css/achievements.css'); ?>" />

<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar">
        <?php include __DIR__ . '/includes/partials/sidebar_user_card.php'; ?>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <h5 class="nav-section-title">Menu Principal</h5>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-house-door"></i><span>Painel</span></a></li>
                    <li class="nav-item"><a href="habits.php" class="nav-link"><i class="bi bi-list-check"></i><span>Meus Hábitos</span><span class="nav-badge"><?php echo $habitCount; ?></span></a></li>
                    <li class="nav-item"><a href="history.php" class="nav-link"><i class="bi bi-graph-up-arrow"></i><span>Histórico</span></a></li>
                    <li class="nav-item"><a href="achievements.php" class="nav-link active"><i class="bi bi-trophy"></i><span>Conquistas</span><span class="nav-badge"><?php echo $unlockedCount; ?></span></a></li>
                </ul>
            </div>
            <div class="nav-section">
                <h5 class="nav-section-title">Conta</h5>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="#" class="nav-link" data-open-settings-modal aria-controls="settingsModalOverlay" aria-haspopup="dialog"><i class="bi bi-gear"></i><span>Configurações</span></a></li>
                    <li class="nav-item"><a href="../actions/logout_action.php" class="nav-link"><i class="bi bi-box-arrow-right"></i><span>Sair</span></a></li>
                </ul>
            </div>
        </nav>
    </aside>

    <main class="dashboard-content achievements-page">
        <section class="dashboard-card achievements-hero">
            <div class="achievements-hero-shell">
                <div class="hero-title">
                    <span class="hero-title-icon" aria-hidden="true"><i class="bi bi-trophy-fill"></i></span>
                    <div>
                        <h1>Conquistas</h1>
                        <p>Seu painel de evolução, raridades e recompensas desbloqueadas.</p>
                    </div>
                </div>

                <div class="hero-grid">
                    <div class="hero-user">
                        <div class="hero-avatar"><?php echo htmlspecialchars((string) ($userData['initials'] ?? 'U'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="hero-user-copy">
                            <strong><?php echo htmlspecialchars((string) ($hero['name'] ?? $userData['name']), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p>Nível <?php echo (int) ($hero['level'] ?? 1); ?> · <?php echo number_format((int) ($hero['total_xp'] ?? 0), 0, ',', '.'); ?> XP</p>
                        </div>
                    </div>

                    <div class="hero-xp-panel">
                        <div class="hero-xp-head">
                            <span>Barra de XP</span>
                            <strong><?php echo (float) ($hero['xp_progress_percent'] ?? 0); ?>%</strong>
                        </div>
                        <div class="xp-bar" aria-label="Progresso de XP atual">
                            <span class="xp-bar-fill" data-xp-progress="<?php echo (float) ($hero['xp_progress_percent'] ?? 0); ?>"></span>
                        </div>
                        <small><?php echo (int) ($hero['xp_to_next_level'] ?? 0); ?> XP para o próximo nível</small>
                    </div>
                </div>

                <div class="hero-chips">
                    <span class="hero-chip hero-chip-highlight"><?php echo $unlockedCount; ?>/<?php echo $totalAvailable; ?> desbloqueadas</span>
                    <span class="hero-chip">Classificação: <?php echo htmlspecialchars((string) ($hero['rank_label'] ?? 'Iniciante'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="hero-chip">Progresso geral: <?php echo (float) ($hero['progress_percent'] ?? 0); ?>%</span>
                    <span class="hero-chip">Lendárias: <?php echo (int) ($stats['legendary_unlocked'] ?? 0); ?></span>
                </div>
            </div>
        </section>

        <section class="dashboard-card recent-achievements-panel" aria-labelledby="recentAchievementsTitle">
            <div class="recent-panel-head">
                <div>
                    <h2 id="recentAchievementsTitle">Conquistas recentes</h2>
                    <p>Suas últimas vitórias na jornada de hábitos.</p>
                </div>
                <span class="recent-panel-badge"><?php echo count($recentUnlocked); ?> exibidas</span>
            </div>

            <?php if (!empty($recentUnlocked)): ?>
                <ol class="recent-achievements-timeline">
                    <?php foreach ($recentUnlocked as $item): ?>
                        <?php
                        $itemRarity = (string) ($item['rarity'] ?? 'common');
                        $itemRarityMeta = $rarityConfig[$itemRarity] ?? $rarityConfig['common'];
                        $itemDate = !empty($item['date']) ? date('d/m/Y', strtotime((string) $item['date'])) : 'Sem data';
                        ?>
                        <li
                            class="recent-achievement-item<?php echo !empty($item['just_unlocked']) ? ' is-new' : ''; ?>"
                            style="--badge-color: <?php echo htmlspecialchars((string) ($item['badge_color'] ?? $itemRarityMeta['color']), ENT_QUOTES, 'UTF-8'); ?>; --badge-glow: <?php echo htmlspecialchars((string) ($itemRarityMeta['glow'] ?? 'rgba(74,116,255,0.25)'), ENT_QUOTES, 'UTF-8'); ?>;">
                            <div class="recent-achievement-marker" aria-hidden="true">
                                <span class="recent-achievement-dot"></span>
                                <span class="recent-achievement-line"></span>
                            </div>
                            <div class="recent-achievement-card">
                                <div class="recent-achievement-icon"><i class="<?php echo htmlspecialchars((string) ($item['icon'] ?? 'bi bi-award'), ENT_QUOTES, 'UTF-8'); ?>"></i></div>
                                <div class="recent-achievement-content">
                                    <div class="recent-achievement-topline">
                                        <span class="rarity-pill rarity-<?php echo htmlspecialchars($itemRarity, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $itemRarityMeta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if (!empty($item['just_unlocked'])): ?><span class="recent-new-badge">Novo</span><?php endif; ?>
                                    </div>
                                    <h3><?php echo htmlspecialchars((string) ($item['name'] ?? 'Conquista'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="recent-achievement-meta">
                                        <span><i class="bi bi-stars"></i> +<?php echo (int) ($item['points'] ?? 0); ?> XP</span>
                                        <span><i class="bi bi-calendar-event"></i> <?php echo $itemDate; ?></span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <div class="recent-achievements-empty">
                    <div class="recent-achievements-empty-icon"><i class="bi bi-trophy"></i></div>
                    <div>
                        <h3>Nenhuma conquista recente ainda</h3>
                        <p>Conclua seus hábitos para começar a colecionar conquistas e aparecer nesta timeline.</p>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="achievement-filters-panel" aria-labelledby="achievementFiltersTitle">
            <div class="achievement-filters-header">
                <div>
                    <h2 id="achievementFiltersTitle">Galeria de conquistas</h2>
                    <p>Filtre por status ou raridade para acompanhar sua progressão.</p>
                </div>
                <p class="achievement-filter-results" data-filter-results role="status" aria-live="polite"></p>
            </div>
            <div class="achievement-filters" aria-label="Filtros de conquistas">
                <?php foreach ($filterLabels as $key => $label): ?>
                    <button
                        type="button"
                        class="doitly-btn doitly-btn-secondary filter-btn<?php echo $key === 'all' ? ' is-active' : ''; ?>"
                        data-filter-btn="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-pressed="<?php echo $key === 'all' ? 'true' : 'false'; ?>"><?php echo $label; ?></button>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="achievements-grid" data-achievements-grid>
            <?php foreach ($achievements as $achievement): ?>
                <?php
                $rarity = (string) ($achievement['rarity'] ?? 'common');
                $rarityMeta = $rarityConfig[$rarity] ?? $rarityConfig['common'];
                $isUnlocked = !empty($achievement['unlocked']);
                $progressPercent = (int) ($achievement['progress_percent'] ?? 0);
                $status = $isUnlocked ? 'unlocked' : ($progressPercent > 0 ? 'in_progress' : 'locked');
                ?>
                <article
                    class="dashboard-card achievement-card <?php echo $status; ?>"
                    data-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"
                    data-rarity="<?php echo htmlspecialchars($rarity, ENT_QUOTES, 'UTF-8'); ?>"
                    data-badge-color="<?php echo htmlspecialchars((string) ($achievement['badge_color'] ?? $rarityMeta['color']), ENT_QUOTES, 'UTF-8'); ?>"
                    data-badge-glow="<?php echo htmlspecialchars((string) $rarityMeta['glow'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="achievement-card-shell">
                        <div class="achievement-head">
                            <span class="achievement-icon-wrap"><i class="<?php echo htmlspecialchars((string) ($achievement['icon'] ?? 'bi bi-award'), ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                            <span class="rarity-pill rarity-<?php echo htmlspecialchars($rarity, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $rarityMeta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                        <div class="achievement-copy">
                            <h3><?php echo htmlspecialchars((string) ($achievement['name'] ?? 'Conquista'), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars((string) ($achievement['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>

                        <div class="achievement-progress" title="<?php echo htmlspecialchars((string) ($achievement['progress_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="achievement-progress-head">
                                <span>Progresso</span>
                                <strong><?php echo $progressPercent; ?>%</strong>
                            </div>
                            <div class="progress-track">
                                <span class="progress-fill" data-progress-percent="<?php echo $progressPercent; ?>"></span>
                            </div>
                            <small><?php echo htmlspecialchars((string) ($achievement['progress_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                        </div>

                        <div class="achievement-footer">
                            <span class="achievement-xp"><i class="bi bi-stars"></i> +<?php echo (int) ($achievement['points'] ?? 0); ?> XP</span>
                            <?php if ($isUnlocked && !empty($achievement['date'])): ?>
                                <span class="achievement-date"><i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y', strtotime((string) $achievement['date'])); ?></span>
                            <?php else: ?>
                                <span class="achievement-date muted"><i class="bi bi-lock"></i> <?php echo $status === 'in_progress' ? 'Em progresso' : 'Bloqueada'; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <div class="dashboard-card achievements-filter-empty" data-filter-empty hidden>
                <div class="achievements-filter-empty-icon"><i class="bi bi-search"></i></div>
                <h3>Nenhuma conquista encontrada</h3>
                <p>Troque o filtro para visualizar outras conquistas.</p>
            </div>
        </section>

        <section class="quick-stats quick-stats-achievements">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Desbloqueadas</span>
                    <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
                </div>
                <h2 class="stat-value"><?php echo $unlockedCount; ?></h2>
                <div class="stat-change positive"><i class="bi bi-trophy"></i><span>total obtido</span></div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-header">
                    <span class="stat-label">Total XP</span>
                    <div class="stat-icon"><i class="bi bi-stars"></i></div>
                </div>
                <h2 class="stat-value"><?php echo number_format((int) ($hero['total_xp'] ?? 0), 0, ',', '.'); ?></h2>
                <div class="stat-change positive"><i class="bi bi-rocket"></i><span>acumulado</span></div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-header">
                    <span class="stat-label">Lendárias</span>
                    <div class="stat-icon"><i class="bi bi-gem"></i></div>
                </div>
                <h2 class="stat-value"><?php echo (int) ($stats['legendary_unlocked'] ?? 0); ?></h2>
                <div class="stat-change neutral"><i class="bi bi-award"></i><span>raridade máxima</span></div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-header">
                    <span class="stat-label">Progresso geral</span>
                    <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                </div>
                <h2 class="stat-value"><?php echo (float) ($stats['overall_progress_percent'] ?? 0); ?>%</h2>
                <div class="stat-change neutral"><i class="bi bi-bullseye"></i><span>da coleção</span></div>
            </div>
        </section>
    </main>
</div>

<script>
(function () {
    const achievementCards = Array.from(document.querySelectorAll('.achievement-card'));
    const filterButtons = Array.from(document.querySelectorAll('[data-filter-btn]'));
    const filterResults = document.querySelector('[data-filter-results]');
    const filterEmptyState = document.querySelector('[data-filter-empty]');
    const defaultFilter = 'all';

    function clampPercent(value) {
        return Math.min(100, Math.max(0, Number(value || 0)));
    }

    function applyBadgeStyles() {
        document.querySelectorAll('[data-badge-color]').forEach(card => {
            const badgeColor = card.dataset.badgeColor;
            const badgeGlow = card.dataset.badgeGlow;

            if (badgeColor) {
                card.style.setProperty('--badge-color', badgeColor);
            }
            if (badgeGlow) {
                card.style.setProperty('--badge-glow', badgeGlow);
            }
        });
    }

    function hydrateProgressBars() {
        document.querySelectorAll('.progress-fill').forEach(fill => {
            fill.style.width = `${clampPercent(fill.dataset.progressPercent)}%`;
        });

        document.querySelectorAll('.xp-bar-fill').forEach(fill => {
            fill.style.width = `${clampPercent(fill.dataset.xpProgress)}%`;
        });
    }

    function setActiveFilter(filterKey) {
        filterButtons.forEach(btn => {
            const isActive = btn.dataset.filterBtn === filterKey;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function updateFilterResultsUI(visibleCount, totalCount, filterKey) {
        const activeButton = filterButtons.find(btn => btn.dataset.filterBtn === filterKey);
        const filterLabel = activeButton ? activeButton.textContent.trim() : 'Todas';

        if (filterResults) {
            filterResults.textContent = `${visibleCount} de ${totalCount} exibidas (${filterLabel})`;
        }

        if (filterEmptyState) {
            filterEmptyState.hidden = visibleCount !== 0;
        }
    }

    function applyFilter(filterKey) {
        let visibleCount = 0;

        achievementCards.forEach(card => {
            const matches = filterKey === 'all'
                || card.dataset.status === filterKey
                || card.dataset.rarity === filterKey;

            card.classList.toggle('is-hidden', !matches);
            if (matches) {
                visibleCount += 1;
            }
        });

        updateFilterResultsUI(visibleCount, achievementCards.length, filterKey);
    }

    function initFilters() {
        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const filterKey = btn.dataset.filterBtn || defaultFilter;
                setActiveFilter(filterKey);
                applyFilter(filterKey);
            });
        });

        setActiveFilter(defaultFilter);
        applyFilter(defaultFilter);
    }

    applyBadgeStyles();
    hydrateProgressBars();
    initFilters();
})();
</script>

<?php include_once 'includes/settings_modal.php'; ?>
<?php include_once 'includes/profile_modal.php'; ?>
<?php include_once 'includes/footer.php'; ?>

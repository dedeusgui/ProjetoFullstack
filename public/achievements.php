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
$highlights = $pageData['highlights'] ?? [];
$stats = $pageData['stats'] ?? [];

$userProgressService = new UserProgressService($conn);
$profileSummary = $userProgressService->refreshUserProgressSummary((int) $userId, $achievements);
$userData['level'] = (int) ($profileSummary['level'] ?? ($hero['level'] ?? 1));

$rarityConfig = [
    'common' => ['label' => 'Comum', 'color' => '#95a5a6', 'glow' => 'rgba(149,165,166,0.3)'],
    'rare' => ['label' => 'Raro', 'color' => '#3498db', 'glow' => 'rgba(52,152,219,0.4)'],
    'epic' => ['label' => 'Épico', 'color' => '#9b59b6', 'glow' => 'rgba(155,89,182,0.4)'],
    'legendary' => ['label' => 'Lendário', 'color' => '#FFD700', 'glow' => 'rgba(255,215,0,0.5)'],
];

$unlockedCount = (int) ($hero['unlocked_count'] ?? 0);

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
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="bi bi-house-door"></i><span>Dashboard</span></a></li>
                    <li class="nav-item"><a href="habits.php" class="nav-link"><i class="bi bi-list-check"></i><span>Meus Hábitos</span></a></li>
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
            <div class="card-body">
                <div class="hero-title"><i class="bi bi-trophy"></i><h1>Conquistas</h1></div>
                <div class="hero-grid">
                    <div class="hero-user">
                        <div class="hero-avatar"><?php echo htmlspecialchars((string) ($userData['initials'] ?? 'U'), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars((string) ($hero['name'] ?? $userData['name']), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p>Nível <?php echo (int) ($hero['level'] ?? 1); ?> · <?php echo number_format((int) ($hero['total_xp'] ?? 0), 0, ',', '.'); ?> XP</p>
                        </div>
                    </div>
                    <div>
                        <div class="xp-bar"><span style="width: <?php echo (float) ($hero['xp_progress_percent'] ?? 0); ?>%"></span></div>
                        <small><?php echo (int) ($hero['xp_to_next_level'] ?? 0); ?> XP para o próximo nível</small>
                    </div>
                </div>
                <div class="hero-chips">
                    <span class="doitly-badge"><?php echo $unlockedCount; ?>/<?php echo (int) ($hero['total_available'] ?? 0); ?> desbloqueadas</span>
                    <span class="doitly-badge">Rank: <?php echo htmlspecialchars((string) ($hero['rank_label'] ?? 'Iniciante'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="doitly-badge">Progresso: <?php echo (float) ($hero['progress_percent'] ?? 0); ?>%</span>
                </div>
            </div>
        </section>

        <section class="achievement-filters" aria-label="Filtros">
            <?php foreach (['all' => 'Todas', 'unlocked' => '🔓 Desbloqueadas', 'in_progress' => '📈 Em progresso', 'locked' => '🔒 Bloqueadas', 'common' => '⚪ Common', 'rare' => '🔵 Rare', 'epic' => '🟣 Epic', 'legendary' => '🟠 Legendary'] as $key => $label): ?>
                <button type="button" class="doitly-btn doitly-btn-secondary filter-btn" data-filter-btn="<?php echo $key; ?>"><?php echo $label; ?></button>
            <?php endforeach; ?>
        </section>

        <section class="achievements-grid">
            <?php foreach ($achievements as $achievement): ?>
                <?php
                $rarity = (string) ($achievement['rarity'] ?? 'common');
                $rarityMeta = $rarityConfig[$rarity] ?? $rarityConfig['common'];
                $isUnlocked = !empty($achievement['unlocked']);
                $progressPercent = (int) ($achievement['progress_percent'] ?? 0);
                $status = $isUnlocked ? 'unlocked' : ($progressPercent > 0 ? 'in_progress' : 'locked');
                ?>
                <article class="dashboard-card achievement-card <?php echo $status; ?>" data-status="<?php echo $status; ?>" data-rarity="<?php echo $rarity; ?>" style="--badge-color: <?php echo htmlspecialchars((string) ($achievement['badge_color'] ?? $rarityMeta['color']), ENT_QUOTES, 'UTF-8'); ?>; --badge-glow: <?php echo htmlspecialchars($rarityMeta['glow'], ENT_QUOTES, 'UTF-8'); ?>;">
                    <div class="card-body">
                        <div class="achievement-head">
                            <i class="<?php echo htmlspecialchars((string) $achievement['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <span class="rarity-pill rarity-<?php echo $rarity; ?>"><?php echo htmlspecialchars($rarityMeta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars((string) $achievement['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars((string) $achievement['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                        <div class="achievement-progress" title="<?php echo htmlspecialchars((string) $achievement['progress_label'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="progress-track"><span style="width: <?php echo $progressPercent; ?>%"></span></div>
                            <small><?php echo htmlspecialchars((string) $achievement['progress_label'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo $progressPercent; ?>%)</small>
                        </div>

                        <div class="achievement-footer">
                            <span>+<?php echo (int) ($achievement['points'] ?? 0); ?> XP</span>
                            <?php if ($isUnlocked && !empty($achievement['date'])): ?>
                                <span><i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y', strtotime((string) $achievement['date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="quick-stats">
            <div class="stat-card"><div class="stat-label">Desbloqueadas</div><h2 class="stat-value"><?php echo $unlockedCount; ?></h2></div>
            <div class="stat-card"><div class="stat-label">Total XP</div><h2 class="stat-value"><?php echo number_format((int) ($hero['total_xp'] ?? 0), 0, ',', '.'); ?></h2></div>
            <div class="stat-card"><div class="stat-label">Lendárias</div><h2 class="stat-value"><?php echo (int) ($stats['legendary_unlocked'] ?? 0); ?></h2></div>
            <div class="stat-card"><div class="stat-label">Prog. Geral</div><h2 class="stat-value"><?php echo (float) ($stats['overall_progress_percent'] ?? 0); ?>%</h2></div>
        </section>

        <section class="dashboard-card highlights-row">
            <div class="card-body">
                <h2>Destaques</h2>
                <div class="highlights-list">
                    <?php foreach (['latest_unlocked' => 'Última conquista', 'rarest_unlocked' => 'Mais rara obtida', 'next_achievement' => 'Próxima conquista'] as $key => $label): ?>
                        <?php $item = $highlights[$key] ?? null; ?>
                        <div class="highlight-item">
                            <small><?php echo $label; ?></small>
                            <strong><?php echo htmlspecialchars((string) ($item['name'] ?? 'Sem dados'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if ($item): ?><span><?php echo (int) ($item['progress_percent'] ?? 0); ?>%</span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
document.querySelectorAll('[data-filter-btn]').forEach(btn => {
    btn.addEventListener('click', () => {
        const filter = btn.dataset.filterBtn;
        document.querySelectorAll('.achievement-card').forEach(card => {
            const match = filter === 'all' || card.dataset.status === filter || card.dataset.rarity === filter;
            card.style.display = match ? '' : 'none';
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>

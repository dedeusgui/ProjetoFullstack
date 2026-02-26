<?php
$profileLevel = (int) ($userData['level'] ?? ($profileSummary['level'] ?? 1));
$profileName = $userData['name'] ?? 'Usuário';
$profileAvatarUrl = $userData['avatar_url'] ?? null;
$profileInitials = $userData['initials'] ?? 'U';
$profileAchievements = $profileSummary['unlocked_achievements'] ?? [];

$memberSinceLabel = 'Data de criação indisponível';
if (!empty($userData['created_at'])) {
    try {
        $createdAt = new DateTime((string) $userData['created_at']);
        $memberSinceLabel = 'Membro desde ' . $createdAt->format('d/m/Y');
    } catch (Throwable $e) {
        $memberSinceLabel = 'Membro desde ' . htmlspecialchars((string) $userData['created_at'], ENT_QUOTES, 'UTF-8');
    }
}
?>

<div id="profileModalOverlay" class="profile-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
    <div class="profile-modal">
        <div class="profile-modal-header">
            <h3 id="profileModalTitle">Perfil do usuário</h3>
            <button type="button" class="doitly-btn doitly-btn-ghost doitly-btn-sm" data-close-profile-modal aria-label="Fechar modal de perfil">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="profile-modal-body">
            <div class="profile-summary-card">
                <div class="profile-user-identity">
                    <div class="profile-user-avatar">
                        <?php if (!empty($profileAvatarUrl)): ?>
                            <img src="<?php echo htmlspecialchars($profileAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar de <?php echo htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4><?php echo htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p><?php echo $memberSinceLabel; ?></p>
                    </div>
                </div>
                <span class="user-level-badge">Nível <?php echo $profileLevel; ?></span>
            </div>

            <h5 class="profile-section-title">Conquistas desbloqueadas</h5>
            <?php if (empty($profileAchievements)): ?>
                <p class="profile-empty-state">Você ainda não desbloqueou conquistas. Continue evoluindo seus hábitos!</p>
            <?php else: ?>
                <ul class="profile-achievements-list">
                    <?php foreach ($profileAchievements as $achievement): ?>
                        <li class="profile-achievement-item">
                            <span class="profile-achievement-icon"><i class="<?php echo htmlspecialchars($achievement['icon'] ?? 'bi bi-award', ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                            <div>
                                <strong><?php echo htmlspecialchars($achievement['name'] ?? 'Conquista', ENT_QUOTES, 'UTF-8'); ?></strong>
                                <p><?php echo htmlspecialchars($achievement['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

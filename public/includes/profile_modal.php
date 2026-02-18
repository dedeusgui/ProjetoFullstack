<?php
$profileLevel = (int) ($userData['level'] ?? ($profileSummary['level'] ?? 1));
$profileName = $userData['name'] ?? 'Usuário';
$profileAchievements = $profileSummary['unlocked_achievements'] ?? [];
?>

<div id="profileModalOverlay" class="profile-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
    <div class="profile-modal">
        <div class="profile-modal-header">
            <h3 id="profileModalTitle">Perfil do Usuário</h3>
            <button type="button" class="doitly-btn doitly-btn-ghost doitly-btn-sm" data-close-profile-modal aria-label="Fechar modal de perfil">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="profile-modal-body">
            <div class="profile-summary">
                <h4><?php echo htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8'); ?></h4>
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

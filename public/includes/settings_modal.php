<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$avatarUrl = trim($userData['avatar_url'] ?? '');
?>

<div id="settingsModalOverlay" class="settings-modal-overlay" aria-hidden="true">
    <div class="settings-modal" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle">
        <div class="settings-modal-header">
            <h3 id="settingsModalTitle">Configurações da Conta</h3>
            <button type="button" class="settings-modal-close" data-close-settings-modal aria-label="Fechar modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="../actions/update_profile_action.php" class="settings-form">
            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="settings-avatar-preview">
                <div class="user-avatar user-avatar-preview" id="settingsAvatarPreview">
                    <?php if ($avatarUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar atual">
                    <?php else: ?>
                        <?php echo htmlspecialchars($userData['initials'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                <p>Pré-visualização da imagem de perfil</p>
            </div>

            <label class="settings-label" for="settingsEmail">E-mail</label>
            <input type="email" id="settingsEmail" name="email" required value="<?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?>" class="settings-input">

            <label class="settings-label" for="settingsAvatarUrl">URL da imagem de perfil</label>
            <input type="url" id="settingsAvatarUrl" name="avatar_url" placeholder="https://..." value="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" class="settings-input" data-avatar-input>

            <div class="settings-divider"></div>

            <p class="settings-password-title">Alterar senha (opcional)</p>

            <label class="settings-label" for="settingsCurrentPassword">Senha atual</label>
            <input type="password" id="settingsCurrentPassword" name="current_password" class="settings-input" autocomplete="current-password">

            <label class="settings-label" for="settingsNewPassword">Nova senha</label>
            <input type="password" id="settingsNewPassword" name="new_password" minlength="6" class="settings-input" autocomplete="new-password">

            <label class="settings-label" for="settingsConfirmPassword">Confirmar nova senha</label>
            <input type="password" id="settingsConfirmPassword" name="confirm_password" minlength="6" class="settings-input" autocomplete="new-password">

            <div class="settings-modal-actions">
                <button type="button" class="doitly-btn doitly-btn-outline" data-close-settings-modal>Cancelar</button>
                <button type="submit" class="doitly-btn">
                    <i class="bi bi-check2-circle"></i>
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</div>

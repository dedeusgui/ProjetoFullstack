<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$avatarUrl = trim($userData['avatar_url'] ?? '');
?>

<div id="settingsModalOverlay" aria-hidden="true" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; padding: var(--space-lg); overflow-y: auto;">
    <div style="max-width: 620px; margin: 40px auto; background: var(--bg-light); border-radius: var(--radius-large); padding: var(--space-xl); box-shadow: var(--shadow-strong); border: var(--border-light);">
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom: var(--space-lg);">
            <h2 style="margin: 0; font-size: 1.5rem;">
                <i class="bi bi-gear"></i> Configurações da Conta
            </h2>
            <button type="button" class="doitly-btn doitly-btn-ghost doitly-btn-sm" data-close-settings-modal aria-label="Fechar modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="../actions/update_profile_action.php" class="d-flex flex-column gap-md">
            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="d-flex flex-column align-items-center" style="gap: 8px; margin-bottom: var(--space-sm);">
                <div class="user-avatar" id="settingsAvatarPreview" style="overflow: hidden;">
                    <?php if ($avatarUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar atual" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
                    <?php else: ?>
                        <?php echo htmlspecialchars($userData['initials'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                <p style="margin: 0; color: var(--text-secondary); font-size: 0.85rem;">Pré-visualização da imagem de perfil</p>
            </div>

            <div class="d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                <div>
                    <label for="settingsEmail" style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">E-mail</label>
                    <input type="email" id="settingsEmail" name="email" required value="<?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                </div>

                <div>
                    <label for="settingsAvatarUrl" style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">URL da imagem de perfil</label>
                    <input type="url" id="settingsAvatarUrl" name="avatar_url" placeholder="https://..." value="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" data-avatar-input>
                </div>
            </div>

            <div style="margin: var(--space-sm) 0 0; padding-top: var(--space-md); border-top: var(--border-light);">
                <h4 style="margin: 0 0 var(--space-md); font-size: 1rem;">Alterar senha (opcional)</h4>
                <div class="d-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: var(--space-md);">
                    <div>
                        <label for="settingsCurrentPassword" style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Senha atual</label>
                        <input type="password" id="settingsCurrentPassword" name="current_password" class="form-control" autocomplete="current-password">
                    </div>
                    <div>
                        <label for="settingsNewPassword" style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Nova senha</label>
                        <input type="password" id="settingsNewPassword" name="new_password" minlength="6" class="form-control" autocomplete="new-password">
                    </div>
                    <div>
                        <label for="settingsConfirmPassword" style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Confirmar nova senha</label>
                        <input type="password" id="settingsConfirmPassword" name="confirm_password" minlength="6" class="form-control" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end" style="gap: var(--space-sm); margin-top: var(--space-md);">
                <button type="button" class="doitly-btn doitly-btn-outline" data-close-settings-modal>Cancelar</button>
                <button type="submit" class="doitly-btn">
                    <i class="bi bi-check2-circle"></i>
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</div>

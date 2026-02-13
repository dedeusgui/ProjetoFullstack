<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$avatarUrl = trim($userData['avatar_url'] ?? '');
$currentTheme = ($userData['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
$primaryColor = $userData['primary_color'] ?? '#4a74ff';
$accentColor = $userData['accent_color'] ?? '#59d186';
$textScale = isset($userData['text_scale']) ? (float) $userData['text_scale'] : 1.00;
?>

<div id="settingsModalOverlay" aria-hidden="true"
    style="display: none; position: fixed; inset: 0; background: var(--overlay-backdrop); backdrop-filter: blur(4px); z-index: 1000; padding: var(--space-lg); overflow-y: auto;"
    data-theme="<?php echo htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>"
    data-primary-color="<?php echo htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8'); ?>"
    data-accent-color="<?php echo htmlspecialchars($accentColor, ENT_QUOTES, 'UTF-8'); ?>"
    data-text-scale="<?php echo htmlspecialchars(number_format($textScale, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
    <div
        style="max-width: 620px; margin: 40px auto; background: var(--bg-light); border-radius: var(--radius-large); padding: var(--space-xl); box-shadow: var(--shadow-strong); border: var(--border-light);">
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom: var(--space-lg);">
            <h2 style="margin: 0; font-size: 1.5rem;">
                <i class="bi bi-gear"></i> Configurações da Conta
            </h2>
            <button type="button" class="doitly-btn doitly-btn-ghost doitly-btn-sm" data-close-settings-modal
                aria-label="Fechar modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="../actions/update_profile_action.php" class="d-flex flex-column gap-md">
            <input type="hidden" name="return_to"
                value="<?php echo htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="theme" id="settingsThemeInput"
                value="<?php echo htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="d-flex flex-column align-items-center" style="gap: 8px; margin-bottom: var(--space-sm);">
                <div class="user-avatar" id="settingsAvatarPreview" style="overflow: hidden;">
                    <?php if ($avatarUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar atual"
                            style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
                    <?php else: ?>
                        <?php echo htmlspecialchars($userData['initials'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>
                <p style="margin: 0; color: var(--text-secondary); font-size: 0.85rem;">Pré-visualização da imagem de
                    perfil</p>
            </div>

            <div class="d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                <div>
                    <label for="settingsEmail"
                        style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">E-mail</label>
                    <input type="email" id="settingsEmail" name="email" required
                        value="<?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="form-control">
                </div>

                <div>
                    <label for="settingsAvatarUrl"
                        style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">URL
                        da imagem de perfil</label>
                    <input type="url" id="settingsAvatarUrl" name="avatar_url" placeholder="https://..."
                        value="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" class="form-control"
                        data-avatar-input>
                </div>
            </div>


            <div style="margin-top: var(--space-sm);">
                <p style="margin: 0 0 var(--space-xs); font-size: 0.875rem; color: var(--text-secondary);">Aparência</p>
                <div class="theme-toggle">
                    <div>
                        <strong style="display:block; font-size: 0.95rem;">Modo escuro</strong>
                        <small style="color: var(--text-secondary);">Ative para reduzir brilho e melhorar o contraste à noite.</small>
                    </div>
                    <label class="theme-toggle-switch" for="themeToggleInput">
                        <input type="checkbox" id="themeToggleInput" data-theme-toggle aria-label="Alternar tema escuro"
                            <?php echo $currentTheme === 'dark' ? 'checked' : ''; ?>>
                        <span class="theme-toggle-slider"></span>
                    </label>
                </div>

                <div class="d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--space-md); margin-top: var(--space-md);">
                    <div>
                        <label for="settingsPrimaryColor"
                            style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Cor principal</label>
                        <input type="color" id="settingsPrimaryColor" name="primary_color"
                            value="<?php echo htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-color" data-primary-color-input>
                    </div>
                    <div>
                        <label for="settingsAccentColor"
                            style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Cor de destaque</label>
                        <input type="color" id="settingsAccentColor" name="accent_color"
                            value="<?php echo htmlspecialchars($accentColor, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-color" data-accent-color-input>
                    </div>
                </div>

                <div style="margin-top: var(--space-md);">
                    <label for="settingsTextScale"
                        style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">
                        Escala de texto
                        <strong id="settingsTextScaleValue"><?php echo number_format($textScale * 100, 0); ?>%</strong>
                        <small id="settingsTextScaleDelta" style="margin-left: 6px; color: var(--text-tertiary);"></small>
                    </label>
                    <input type="range" id="settingsTextScale" name="text_scale" min="0.9" max="1.2" step="0.05"
                        value="<?php echo htmlspecialchars(number_format($textScale, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" class="form-range" data-text-scale-input>
                    <small style="display:block; margin-top: 6px; color: var(--text-tertiary);">Ajuste de 90% até 120% (100% é o padrão).</small>
                </div>

                <div style="margin-top: var(--space-sm); display:flex; justify-content:flex-end;">
                    <button type="button" class="doitly-btn doitly-btn-outline doitly-btn-sm" data-reset-appearance>
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Retornar ao padrão
                    </button>
                </div>
            </div>

            <div style="margin: var(--space-sm) 0 0; padding-top: var(--space-md); border-top: var(--border-light);">
                <h4 style="margin: 0 0 var(--space-md); font-size: 1rem;">Alterar senha (opcional)</h4>
                <div class="d-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: var(--space-md);">
                    <div>
                        <label for="settingsCurrentPassword"
                            style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Senha
                            atual</label>
                        <div style="position: relative;">
                            <input type="password" id="settingsCurrentPassword" name="current_password"
                                class="form-control" autocomplete="current-password" style="padding-right: 40px;">
                            <button type="button" class="btn btn-link position-absolute"
                                onclick="togglePasswordVisibility('settingsCurrentPassword', this)"
                                style="right: 4px; top: 50%; transform: translateY(-50%); padding: 4px 8px; color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;"
                                title="Mostrar/Ocultar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="settingsNewPassword"
                            style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Nova
                            senha</label>
                        <div style="position: relative;">
                            <input type="password" id="settingsNewPassword" name="new_password" minlength="6"
                                class="form-control" autocomplete="new-password" style="padding-right: 40px;">
                            <button type="button" class="btn btn-link position-absolute"
                                onclick="togglePasswordVisibility('settingsNewPassword', this)"
                                style="right: 4px; top: 50%; transform: translateY(-50%); padding: 4px 8px; color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;"
                                title="Mostrar/Ocultar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="settingsConfirmPassword"
                            style="display:block; margin-bottom: 6px; font-size: 0.875rem; color: var(--text-secondary);">Confirmar
                            nova senha</label>
                        <div style="position: relative;">
                            <input type="password" id="settingsConfirmPassword" name="confirm_password" minlength="6"
                                class="form-control" autocomplete="new-password" style="padding-right: 40px;">
                            <button type="button" class="btn btn-link position-absolute"
                                onclick="togglePasswordVisibility('settingsConfirmPassword', this)"
                                style="right: 4px; top: 50%; transform: translateY(-50%); padding: 4px 8px; color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;"
                                title="Mostrar/Ocultar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: var(--space-sm); margin-top: var(--space-md);">
                <a href="../actions/export_user_data_csv.php" class="doitly-btn doitly-btn-outline">
                    <i class="bi bi-download"></i>
                    Exportar resumo (CSV)
                </a>

                <div class="d-flex justify-content-end" style="gap: var(--space-sm);">
                    <button type="button" class="doitly-btn doitly-btn-outline" data-close-settings-modal>Cancelar</button>
                    <button type="submit" class="doitly-btn">
                        <i class="bi bi-check2-circle"></i>
                        Salvar alterações
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../config/bootstrap.php';
bootApp();

actionRequireLoggedIn();
$allowedReturnPages = ['dashboard.php', 'habits.php', 'history.php'];
$redirectPath = actionResolveReturnPath($allowedReturnPages, 'dashboard.php');
actionRequirePost('dashboard.php');

$userId = (int) getUserId();
$defaultTheme = 'light';
$defaultPrimaryColor = '#4A74FF';
$defaultAccentColor = '#59D186';
$defaultTextScale = 1.00;

$stmt = $conn->prepare(
    'INSERT INTO user_settings (user_id, theme, primary_color, accent_color, text_scale)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        theme = VALUES(theme),
        primary_color = VALUES(primary_color),
        accent_color = VALUES(accent_color),
        text_scale = VALUES(text_scale),
        updated_at = CURRENT_TIMESTAMP'
);
$stmt->bind_param('isssd', $userId, $defaultTheme, $defaultPrimaryColor, $defaultAccentColor, $defaultTextScale);

if ($stmt->execute()) {
    actionFlashAndRedirect('success_message', 'Aparência restaurada para o padrão do site.', $redirectPath);
}

actionFlashAndRedirect('error_message', 'Não foi possível restaurar as configurações de aparência.', $redirectPath);

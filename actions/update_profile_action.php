<?php
require_once '../config/bootstrap.php';
bootApp();

actionRequireLoggedIn();
$allowedReturnPages = ['dashboard.php', 'habits.php', 'history.php'];
$redirectPath = actionResolveReturnPath($allowedReturnPages, 'dashboard.php');
actionRequirePost('dashboard.php');

$userId = (int) getUserId();
$email = trim($_POST['email'] ?? '');
$avatarUrl = trim($_POST['avatar_url'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');
$currentPassword = trim($_POST['current_password'] ?? '');
$theme = ($_POST['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
$primaryColor = strtoupper(trim($_POST['primary_color'] ?? '#4A74FF'));
$accentColor = strtoupper(trim($_POST['accent_color'] ?? '#59D186'));
$textScale = (float) ($_POST['text_scale'] ?? 1.00);

if ($email === '') {
    actionFlashAndRedirect('error_message', 'O e-mail é obrigatório.', $redirectPath);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    actionFlashAndRedirect('error_message', 'Informe um e-mail válido.', $redirectPath);
}
if ($avatarUrl !== '' && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
    actionFlashAndRedirect('error_message', 'A URL da imagem de perfil é inválida.', $redirectPath);
}
if (!preg_match('/^#[0-9A-F]{6}$/', $primaryColor)) {
    actionFlashAndRedirect('error_message', 'A cor principal é inválida.', $redirectPath);
}
if (!preg_match('/^#[0-9A-F]{6}$/', $accentColor)) {
    actionFlashAndRedirect('error_message', 'A cor de destaque é inválida.', $redirectPath);
}
if ($textScale < 0.9 || $textScale > 1.2) {
    actionFlashAndRedirect('error_message', 'O ajuste de tamanho de texto está fora do limite permitido.', $redirectPath);
}

$checkEmailStmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
$checkEmailStmt->bind_param('si', $email, $userId);
$checkEmailStmt->execute();
if ($checkEmailStmt->get_result()->num_rows > 0) {
    actionFlashAndRedirect('error_message', 'Este e-mail já está em uso por outro usuário.', $redirectPath);
}

$shouldUpdatePassword = $newPassword !== '' || $confirmPassword !== '';
$passwordHash = null;

if ($shouldUpdatePassword) {
    if ($newPassword === '' || $confirmPassword === '') {
        actionFlashAndRedirect('error_message', 'Para alterar a senha, preencha os campos de nova senha e confirmação.', $redirectPath);
    }
    if (strlen($newPassword) < 6) {
        actionFlashAndRedirect('error_message', 'A nova senha deve ter ao menos 6 caracteres.', $redirectPath);
    }
    if ($newPassword !== $confirmPassword) {
        actionFlashAndRedirect('error_message', 'A confirmação da nova senha não confere.', $redirectPath);
    }
    if ($currentPassword === '') {
        actionFlashAndRedirect('error_message', 'Informe a senha atual para confirmar a alteração.', $redirectPath);
    }

    $currentPasswordStmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $currentPasswordStmt->bind_param('i', $userId);
    $currentPasswordStmt->execute();
    $currentPasswordResult = $currentPasswordStmt->get_result()->fetch_assoc();

    if (!$currentPasswordResult || !password_verify($currentPassword, $currentPasswordResult['password'])) {
        actionFlashAndRedirect('error_message', 'A senha atual está incorreta.', $redirectPath);
    }

    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
}

if ($shouldUpdatePassword) {
    $updateStmt = $conn->prepare('UPDATE users SET email = ?, avatar_url = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $updateStmt->bind_param('sssi', $email, $avatarUrl, $passwordHash, $userId);
} else {
    $updateStmt = $conn->prepare('UPDATE users SET email = ?, avatar_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $updateStmt->bind_param('ssi', $email, $avatarUrl, $userId);
}

$conn->begin_transaction();

try {
    if (!$updateStmt->execute()) {
        throw new Exception('Falha ao atualizar dados básicos do usuário.');
    }

    $settingsStmt = $conn->prepare(
        'INSERT INTO user_settings (user_id, theme, primary_color, accent_color, text_scale)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            theme = VALUES(theme),
            primary_color = VALUES(primary_color),
            accent_color = VALUES(accent_color),
            text_scale = VALUES(text_scale),
            updated_at = CURRENT_TIMESTAMP'
    );
    $settingsStmt->bind_param('isssd', $userId, $theme, $primaryColor, $accentColor, $textScale);

    if (!$settingsStmt->execute()) {
        throw new Exception('Falha ao atualizar preferências visuais do usuário.');
    }

    $conn->commit();
    $_SESSION['user_email'] = $email;
    $message = $shouldUpdatePassword
        ? 'Configurações atualizadas com sucesso! E sua senha foi alterada.'
        : 'Configurações atualizadas com sucesso!';

    actionFlashAndRedirect('success_message', $message, $redirectPath);
} catch (Throwable $exception) {
    $conn->rollback();
    actionFlashAndRedirect('error_message', 'Não foi possível salvar as configurações. Tente novamente.', $redirectPath);
}

<?php
session_start();

require_once '../config/conexao.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

$returnTo = trim($_POST['return_to'] ?? 'dashboard.php');
$allowedReturnPages = ['dashboard.php', 'habits.php', 'history.php'];
if (!in_array($returnTo, $allowedReturnPages, true)) {
    $returnTo = 'dashboard.php';
}
$redirectPath = '../public/' . $returnTo;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectPath);
    exit;
}

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
    $_SESSION['success_message'] = 'Aparência restaurada para o padrão do site.';
} else {
    $_SESSION['error_message'] = 'Não foi possível restaurar as configurações de aparência.';
}

header('Location: ' . $redirectPath);
exit;

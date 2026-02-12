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
$email = trim($_POST['email'] ?? '');
$avatarUrl = trim($_POST['avatar_url'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');
$currentPassword = trim($_POST['current_password'] ?? '');

if ($email === '') {
    $_SESSION['error_message'] = 'O e-mail é obrigatório.';
    header('Location: ' . $redirectPath);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = 'Informe um e-mail válido.';
    header('Location: ' . $redirectPath);
    exit;
}

if ($avatarUrl !== '' && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
    $_SESSION['error_message'] = 'A URL da imagem de perfil é inválida.';
    header('Location: ' . $redirectPath);
    exit;
}

$checkEmailStmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
$checkEmailStmt->bind_param('si', $email, $userId);
$checkEmailStmt->execute();
$checkEmailResult = $checkEmailStmt->get_result();

if ($checkEmailResult->num_rows > 0) {
    $_SESSION['error_message'] = 'Este e-mail já está em uso por outro usuário.';
    header('Location: ' . $redirectPath);
    exit;
}

$shouldUpdatePassword = $newPassword !== '' || $confirmPassword !== '';
$passwordHash = null;

if ($shouldUpdatePassword) {
    if ($newPassword === '' || $confirmPassword === '') {
        $_SESSION['error_message'] = 'Para alterar a senha, preencha os campos de nova senha e confirmação.';
        header('Location: ' . $redirectPath);
        exit;
    }

    if (strlen($newPassword) < 6) {
        $_SESSION['error_message'] = 'A nova senha deve ter ao menos 6 caracteres.';
        header('Location: ' . $redirectPath);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = 'A confirmação da nova senha não confere.';
        header('Location: ' . $redirectPath);
        exit;
    }

    if ($currentPassword === '') {
        $_SESSION['error_message'] = 'Informe a senha atual para confirmar a alteração.';
        header('Location: ' . $redirectPath);
        exit;
    }

    $currentPasswordStmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $currentPasswordStmt->bind_param('i', $userId);
    $currentPasswordStmt->execute();
    $currentPasswordResult = $currentPasswordStmt->get_result()->fetch_assoc();

    if (!$currentPasswordResult || !password_verify($currentPassword, $currentPasswordResult['password'])) {
        $_SESSION['error_message'] = 'A senha atual está incorreta.';
        header('Location: ' . $redirectPath);
        exit;
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

if ($updateStmt->execute()) {
    $_SESSION['user_email'] = $email;
    $_SESSION['success_message'] = $shouldUpdatePassword
        ? 'Configurações atualizadas com sucesso! E sua senha foi alterada.'
        : 'Configurações atualizadas com sucesso!';
} else {
    $_SESSION['error_message'] = 'Não foi possível salvar as configurações. Tente novamente.';
}

header('Location: ' . $redirectPath);
exit;

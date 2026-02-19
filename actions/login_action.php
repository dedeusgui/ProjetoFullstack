<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/auth/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
    header('Location: ../public/login.php');
    exit;
}

$authService = new AuthService($conn);
$user = $authService->authenticate($email, $password);

if (!$user) {
    $_SESSION['error_message'] = 'Email ou senha incorretos.';
    header('Location: ../public/login.php');
    exit;
}

login($user['id'], $user['name'], $user['email']);
$authService->updateLastLogin($user['id']);

header('Location: ../public/dashboard.php');
exit;

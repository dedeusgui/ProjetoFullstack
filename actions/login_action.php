<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/auth/AuthService.php';

actionRequirePost('login.php');

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    actionFlashAndRedirect('error_message', 'Por favor, preencha todos os campos.', '../public/login.php');
}

$authService = new AuthService($conn);
$user = $authService->authenticate($email, $password);

if (!$user) {
    actionFlashAndRedirect('error_message', 'Email ou senha incorretos.', '../public/login.php');
}

login($user['id'], $user['name'], $user['email']);
$authService->updateLastLogin($user['id']);

actionRedirect('../public/dashboard.php');

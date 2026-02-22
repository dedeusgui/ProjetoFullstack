<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Auth\AuthService;

actionRequirePost('login.php');
actionRequireCsrf('login.php');

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    actionFlashAndRedirect('error_message', 'Por favor, preencha todos os campos.', '../public/login.php');
}

if (authIsRateLimited()) {
    actionFlashAndRedirect('error_message', 'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.', '../public/login.php');
}

$authService = new AuthService($conn);
$user = $authService->authenticate($email, $password);

if (!$user) {
    authRegisterFailure();
    actionFlashAndRedirect('error_message', 'Email ou senha incorretos.', '../public/login.php');
}

authClearFailures();
signInUser($user['id'], $user['name'], $user['email']);
$authService->updateLastLogin($user['id']);

actionRedirect('../public/dashboard.php');

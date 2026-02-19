<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/auth/AuthService.php';

actionRequirePost('register.php');

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    actionFlashAndRedirect('error_message', 'Por favor, preencha todos os campos.', '../public/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    actionFlashAndRedirect('error_message', 'Email inválido.', '../public/register.php');
}

if (strlen($password) < 6) {
    actionFlashAndRedirect('error_message', 'A senha deve ter no mínimo 6 caracteres.', '../public/register.php');
}

$authService = new AuthService($conn);
if ($authService->emailExists($email)) {
    actionFlashAndRedirect('error_message', 'Este email já está cadastrado.', '../public/register.php');
}

$user = $authService->register($name, $email, $password);
if (!$user) {
    actionFlashAndRedirect('error_message', 'Erro ao criar conta. Tente novamente.', '../public/register.php');
}

login($user['id'], $user['name'], $user['email']);

actionRedirect('../public/dashboard.php');

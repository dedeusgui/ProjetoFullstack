<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Auth\AuthService;

actionRequirePost('register.php');
actionRequireCsrf('register.php');

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
    actionFlashAndRedirect('error_message', 'Por favor, preencha todos os campos.', '../public/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    actionFlashAndRedirect('error_message', 'Email inválido.', '../public/register.php');
}

if (strlen($password) < 6) {
    actionFlashAndRedirect('error_message', 'A senha deve ter no mínimo 6 caracteres.', '../public/register.php');
}

if ($password !== $confirmPassword) {
    actionFlashAndRedirect('error_message', 'As senhas não conferem.', '../public/register.php');
}

$authService = new AuthService($conn);
if ($authService->emailExists($email)) {
    actionFlashAndRedirect('error_message', 'Este email já está cadastrado.', '../public/register.php');
}

$user = $authService->register($name, $email, $password);
if (!$user) {
    actionFlashAndRedirect('error_message', 'Erro ao criar conta. Tente novamente.', '../public/register.php');
}

signInUser($user['id'], $user['name'], $user['email']);

actionRedirect('../public/dashboard.php');

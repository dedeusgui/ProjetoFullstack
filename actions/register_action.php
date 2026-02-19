<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/auth/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/register.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
    header('Location: ../public/register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = 'Email inválido.';
    header('Location: ../public/register.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error_message'] = 'A senha deve ter no mínimo 6 caracteres.';
    header('Location: ../public/register.php');
    exit;
}

$authService = new AuthService($conn);
if ($authService->emailExists($email)) {
    $_SESSION['error_message'] = 'Este email já está cadastrado.';
    header('Location: ../public/register.php');
    exit;
}

$user = $authService->register($name, $email, $password);
if (!$user) {
    $_SESSION['error_message'] = 'Erro ao criar conta. Tente novamente.';
    header('Location: ../public/register.php');
    exit;
}

login($user['id'], $user['name'], $user['email']);

header('Location: ../public/dashboard.php');
exit;

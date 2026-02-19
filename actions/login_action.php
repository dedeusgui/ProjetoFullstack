<?php
require_once '../config/bootstrap.php';
bootApp();

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/login.php');
    exit;
}

// Pegar dados do formulário
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validar campos
if (empty($email) || empty($password)) {
    $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
    header('Location: ../public/login.php');
    exit;
}

// Buscar usuário no banco
$stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ? AND is_active = 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Email ou senha incorretos.';
    header('Location: ../public/login.php');
    exit;
}

$user = $result->fetch_assoc();

// Verificar senha
if (!password_verify($password, $user['password'])) {
    $_SESSION['error_message'] = 'Email ou senha incorretos.';
    header('Location: ../public/login.php');
    exit;
}

// Login bem-sucedido
login((int) $user['id'], $user['name'], $user['email']);

// Atualizar last_login
$updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$updateStmt->bind_param("i", $user['id']);
$updateStmt->execute();

// Redirecionar para dashboard
header('Location: ../public/dashboard.php');
exit;

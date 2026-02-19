<?php
require_once '../config/bootstrap.php';
bootApp();

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/register.php');
    exit;
}

// Pegar dados do formulário
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validar campos
if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error_message'] = 'Por favor, preencha todos os campos.';
    header('Location: ../public/register.php');
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = 'Email inválido.';
    header('Location: ../public/register.php');
    exit;
}

// Validar senha (mínimo 6 caracteres)
if (strlen($password) < 6) {
    $_SESSION['error_message'] = 'A senha deve ter no mínimo 6 caracteres.';
    header('Location: ../public/register.php');
    exit;
}

// Verificar se email já existe
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error_message'] = 'Este email já está cadastrado.';
    header('Location: ../public/register.php');
    exit;
}

// Hash da senha
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Inserir usuário
$insertStmt = $conn->prepare("INSERT INTO users (name, email, password, is_active, email_verified) VALUES (?, ?, ?, 1, 0)");
$insertStmt->bind_param("sss", $name, $email, $passwordHash);

if (!$insertStmt->execute()) {
    $_SESSION['error_message'] = 'Erro ao criar conta. Tente novamente.';
    header('Location: ../public/register.php');
    exit;
}

// Pegar ID do usuário criado
$userId = (int) $insertStmt->insert_id;

// Fazer login automático
login($userId, $name, $email);

// Redirecionar para dashboard
header('Location: ../public/dashboard.php');
exit;

<?php
// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Obter ID do usuário logado
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Obter dados do usuário logado
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = getUserId();
    $stmt = $conn->prepare("SELECT id, name, email, avatar_url FROM users WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Fazer login
function login($userId, $userName, $userEmail) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['logged_in_at'] = time();
}

// Fazer logout
function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Proteger página (redirecionar se não logado)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Gerar iniciais do nome
function getInitials($name) {
    $parts = explode(' ', $name);
    $initials = '';
    
    if (count($parts) >= 2) {
        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    } else {
        $initials = strtoupper(substr($name, 0, 2));
    }
    
    return $initials;
}

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
    static $hasLevelColumn = null;
    static $hasXpColumn = null;

    if ($hasLevelColumn === null) {
        $levelCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'level'");
        $xpCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'experience_points'");
        $hasLevelColumn = $levelCheck && $levelCheck->num_rows > 0;
        $hasXpColumn = $xpCheck && $xpCheck->num_rows > 0;
    }

    $levelSelect = $hasLevelColumn ? 'u.level' : '1';
    $xpSelect = $hasXpColumn ? 'u.experience_points' : '0';

    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.name,
            u.email,
            u.avatar_url,
            u.created_at,
            {$levelSelect} AS level,
            {$xpSelect} AS experience_points,
            us.theme,
            us.primary_color,
            us.accent_color,
            us.text_scale
        FROM users u
        LEFT JOIN user_settings us ON us.user_id = u.id
        WHERE u.id = ? AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Fazer login
function login($userId, $userName, $userEmail) {
    session_regenerate_id(true);
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

<?php

function isUserLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getAuthenticatedUserId(): ?int
{
    $userId = $_SESSION['user_id'] ?? null;
    return $userId !== null ? (int) $userId : null;
}

function getAuthenticatedUserRecord(mysqli $conn): ?array
{
    if (!isUserLoggedIn()) {
        return null;
    }

    $userId = getAuthenticatedUserId();
    if ($userId === null) {
        return null;
    }

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
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

function signInUser(int $userId, string $userName, string $userEmail): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['logged_in_at'] = time();
}

function signOutUser(): void
{
    session_unset();
    session_destroy();
    header('Location: ../public/login.php');
    exit;
}

function requireAuthenticatedUser(): void
{
    if (!isUserLoggedIn()) {
        header('Location: ../public/login.php');
        exit;
    }
}

function getUserInitials(string $name): string
{
    $parts = explode(' ', $name);

    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }

    return strtoupper(substr($name, 0, 2));
}

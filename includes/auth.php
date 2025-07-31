<?php
require_once __DIR__ . '/db.php';

session_start();

if (!isset($_SESSION['user_id']) || (time() - ($_SESSION['last_active'] ?? 0) > 1800)) {
    session_unset();
    session_destroy();
    header('Location: /login.php');
    exit;
}

$_SESSION['last_active'] = time();

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentUserRole(): string {
    return $_SESSION['user_role'] ?? 'user';
}

function isAdmin(): bool {
    return currentUserRole() === 'admin';
}
?>

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

if (!isset($_SESSION['role'])) {
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id=?');
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['role'] = $stmt->fetchColumn() ?: 'firma';
}
?>

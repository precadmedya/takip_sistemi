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
?>

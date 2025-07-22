<?php
session_start();
if (isset($_SESSION['user_id']) && (time() - ($_SESSION['last_active'] ?? 0) < 1800)) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}

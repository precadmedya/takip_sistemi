<?php
require __DIR__.'/includes/auth.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = 'DELETE FROM customers WHERE id=?';
$params = [$id];
if($_SESSION['role'] !== 'admin') {
    $sql .= ' AND user_id=?';
    $params[] = $_SESSION['user_id'];
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
header('Location: customers.php');
exit;

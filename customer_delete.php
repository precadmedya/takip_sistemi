<?php
require __DIR__.'/includes/auth.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('DELETE FROM customers WHERE id=?');
$stmt->execute([$id]);
header('Location: /customers.php');
exit;

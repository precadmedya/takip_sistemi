<?php
require __DIR__.'/includes/auth.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('DELETE FROM services WHERE id=?');
$stmt->execute([$id]);
header('Location: services.php');
exit;

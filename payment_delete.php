<?php
require __DIR__.'/includes/auth.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT service_id FROM payments WHERE id=?');
$stmt->execute([$id]);
$service_id = $stmt->fetchColumn();
$del = $pdo->prepare('DELETE FROM payments WHERE id=?');
$del->execute([$id]);
$redirect = $service_id ? 'service.php?id='.$service_id : 'customers.php';
header('Location: '.$redirect);
exit;

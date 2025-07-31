<?php
require __DIR__.'/includes/auth.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql='DELETE FROM services WHERE id=?';
$params=[$id];
if(!isAdmin()){ $sql.=' AND user_id=?'; $params[] = currentUserId(); }
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
header('Location: services.php');
exit;

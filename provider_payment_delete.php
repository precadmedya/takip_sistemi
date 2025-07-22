<?php
require __DIR__.'/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT provider_id FROM provider_payments WHERE id=?');
$stmt->execute([$id]);
$provider_id = $stmt->fetchColumn();
$pdo->prepare('DELETE FROM provider_payments WHERE id=?')->execute([$id]);
header('Location: provider.php?id='.$provider_id);
exit;

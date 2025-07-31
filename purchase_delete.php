<?php
require __DIR__.'/includes/auth.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT provider_id FROM provider_purchases WHERE id=?');
$stmt->execute([$id]);
$provider_id = $stmt->fetchColumn();
if($provider_id){
    $del = $pdo->prepare('DELETE FROM provider_purchases WHERE id=?');
    $del->execute([$id]);
    $_SESSION['message']='Satın alım silindi';
    header('Location: provider.php?id='.$provider_id);
    exit;
}
header('Location: providers.php');

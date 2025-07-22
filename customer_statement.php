<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$customer){
    exit('Müşteri bulunamadı');
}
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=statement_'.$id.'.csv');
$out = fopen('php://output','w');
fputcsv($out,['Tarih','Açıklama','Tutar (TL)']);
$svcStmt = $pdo->prepare('SELECT site_name,due_date,price_try,vat_rate FROM services WHERE customer_id=?');
$svcStmt->execute([$id]);
while($s = $svcStmt->fetch(PDO::FETCH_ASSOC)){
    $total = $s['price_try']*(1+$s['vat_rate']/100);
    fputcsv($out,[date('d.m.Y',strtotime($s['due_date'])),'Hizmet: '.$s['site_name'],$total]);
}
$payStmt = $pdo->prepare('SELECT amount_try,currency,created_at FROM payments WHERE customer_id=?');
$payStmt->execute([$id]);
while($p = $payStmt->fetch(PDO::FETCH_ASSOC)){
    fputcsv($out,[date('d.m.Y',strtotime($p['created_at'])),'Tahsilat',$p['amount_try']*-1]);
}
fclose($out);

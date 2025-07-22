<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
require __DIR__.'/includes/SimplePDF.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$customer){
    exit('Müşteri bulunamadı');
}

$setStmt = $pdo->prepare('SELECT `key`,value FROM settings WHERE `key` IN ("logo")');
$setStmt->execute();
$settings = [];
foreach($setStmt->fetchAll(PDO::FETCH_ASSOC) as $r){
    $settings[$r['key']] = $r['value'];
}

$usdRate = getUsdRate($pdo);

$pdf = new SimplePDF();
$pdf->AddPage();
$pdf->SetFont('Helvetica','',16);
if(!empty($settings['logo']) && file_exists($settings['logo'])){
    $pdf->Image($settings['logo'],10,10,40);
}
$pdf->Ln(20);
$pdf->Cell(190,10,'Hesap Ekstresi',0,1);
$pdf->SetFont('Helvetica','',12);
$pdf->Cell(190,8,$customer['full_name'],0,1);
$pdf->Ln(4);
$pdf->Cell(40,8,'Tarih');
$pdf->Cell(110,8,'Açıklama');
$pdf->Cell(40,8,'Tutar (TL)',0,1);

$svcStmt = $pdo->prepare('SELECT site_name,due_date,price_try,vat_rate FROM services WHERE customer_id=?');
$svcStmt->execute([$id]);
while($s = $svcStmt->fetch(PDO::FETCH_ASSOC)){
    $total = $s['price_try']*(1+$s['vat_rate']/100);
    $pdf->Cell(40,8,date('d.m.Y',strtotime($s['due_date'])));
    $pdf->Cell(110,8,'Hizmet: '.$s['site_name']);
    $pdf->Cell(40,8,number_format($total,2,',','.'),0,1);
}
$payStmt = $pdo->prepare('SELECT amount_try,currency,created_at FROM payments WHERE customer_id=?');
$payStmt->execute([$id]);
while($p = $payStmt->fetch(PDO::FETCH_ASSOC)){
    $pdf->Cell(40,8,date('d.m.Y',strtotime($p['created_at'])));
    $pdf->Cell(110,8,'Tahsilat');
    $pdf->Cell(40,8,number_format(-$p['amount_try'],2,',','.'),0,1);
}
$pdf->Output('ekstre_'.$id.'.pdf');

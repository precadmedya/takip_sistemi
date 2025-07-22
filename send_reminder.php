<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
$id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$stmt = $pdo->prepare("SELECT s.id,s.site_name,s.due_date,c.id AS cid,c.full_name,c.email FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.id=?");
$stmt->execute([$id]);
$svc = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$svc){
    header('Location: services.php');
    exit;
}
$subject = 'Domain süreniz dolmak üzere';
$body = "Sayın {$svc['full_name']},<br><br>{$svc['site_name']} hizmetinizin bitiş tarihi yaklaşmaktadır. Bitiş tarihi: ".date('d.m.Y',strtotime($svc['due_date'])).".<br><br>Hizmet süresini uzatmak veya ödeme yapmak için lütfen bizimle iletişime geçin.";
$sent = sendMail($pdo,$svc['email'],$subject,$body);
$admins=$pdo->query("SELECT email FROM users")->fetchAll(PDO::FETCH_COLUMN);
foreach($admins as $ad){
    sendMail($pdo,$ad,$subject,$body);
}
$log=$pdo->prepare('INSERT INTO email_logs(service_id,client_id,email_to,subject,content,sent_at) VALUES (?,?,?,?,?,NOW())');
$log->execute([$svc['id'],$svc['cid'],$svc['email'],$subject,$body]);
$_SESSION['message']=$sent?'Hatırlatma gönderildi':'Hatırlatma gönderilemedi';
header('Location: service.php?id='.$id);

<?php
require __DIR__.'/includes/db.php';
require __DIR__.'/includes/functions.php';
$date = date('Y-m-d');
$stmt=$pdo->query("SELECT s.id,s.due_date,s.reminder_days,c.id AS cid,c.full_name,c.email,s.site_name FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.reminder_enabled=1");
$admins=$pdo->query("SELECT email FROM users")->fetchAll(PDO::FETCH_COLUMN);
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $svc){
    $days=(strtotime($svc['due_date'])-strtotime($date))/86400;
    $sel=array_filter(array_map('intval',explode(',',$svc['reminder_days'])));
    if(in_array($days,$sel)){
        $err='';
        $subject='Domain süreniz dolmak üzere';
        $body="Sayın {$svc['full_name']},<br><br>{$svc['site_name']} hizmetinizin bitiş tarihi yaklaşmaktadır. Bitiş tarihi: ".date('d.m.Y',strtotime($svc['due_date'])).".<br><br>Hizmet süresini uzatmak veya ödeme yapmak için lütfen bizimle iletişime geçin.";
        sendMail($pdo,$svc['email'],$subject,$body,$err,$svc['id'],$svc['cid']);
        foreach($admins as $a){sendMail($pdo,$a,$subject,$body,$err,$svc['id'],$svc['cid']);}
    }
}

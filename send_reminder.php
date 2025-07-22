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
$usdRate = getUsdRate($pdo);
$itemsStmt = $pdo->prepare('SELECT * FROM service_items WHERE service_id=?');
$itemsStmt->execute([$svc['id']]);
$rows='';
$total=0; $vatT=0;
foreach($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $it){
    $sub=$it['unit_price']*$it['quantity'];
    $vat=$sub*$it['vat_rate']/100;
    $ttl=$sub+$vat;
    if($it['currency']==='USD'){ $sub*=$usdRate; $vat*=$usdRate; $ttl*=$usdRate; }
    $total+=$sub; $vatT+=$vat;
    $rows.='<tr><td>'.htmlspecialchars($it['item_name']).'</td><td>'.$it['quantity'].' '.htmlspecialchars($it['unit']).'</td><td>'.number_format($it['unit_price'],2,',','.').'</td><td>'.$it['vat_rate'].'%</td><td>'.number_format($vat,2,',','.').'</td><td>'.number_format($ttl,2,',','.').'</td><td>'.htmlspecialchars($svc['site_name']).'</td></tr>';
}
$grand=$total+$vatT;
$settings=getEmailSettings($pdo);
$subject = 'Domain süreniz dolmak üzere';
$body = "Sayın {$svc['full_name']},<br><br>{$svc['site_name']} hizmetinizin bitiş tarihi yaklaşmaktadır. Bitiş tarihi: ".date('d.m.Y',strtotime($svc['due_date'])).".<br><br>";
$body .= '<table border="1" cellpadding="5" style="border-collapse:collapse">';
$body .= '<tr><th>Hizmet</th><th>Süre</th><th>Birim Fiyat</th><th>KDV Oranı</th><th>KDV Tutarı</th><th>Toplam</th><th>Site</th></tr>'.$rows.'</table>';
$body .= '<br><strong>Birim Fiyatı:</strong> '.number_format($total,2,',','.').' TL<br>';
$body .= '<strong>KDV Tutarı:</strong> '.number_format($vatT,2,',','.').' TL<br>';
$body .= '<strong>Genel Toplam:</strong> '.number_format($grand,2,',','.').' TL<br><br>';
$body .= 'Hizmet süresini uzatmak veya ödeme yapmak için lütfen bizimle iletişime geçin.<br><br>Saygılarımızla,<br>'.$settings['from_name'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $subject=$_POST['subject'];
    $body=$_POST['body'];
    $err='';
    sendMail($pdo,$svc['email'],$subject,$body,$err,$svc['id'],$svc['cid']);
    $admins=$pdo->query("SELECT email FROM users")->fetchAll(PDO::FETCH_COLUMN);
    foreach($admins as $ad){sendMail($pdo,$ad,$subject,$body,$err,$svc['id'],$svc['cid']);}
    $_SESSION['message']=$err?'Hatırlatma gönderilemedi: '.$err:'Hatırlatma gönderildi';
    header('Location: service.php?id='.$id);
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Hatırlatma Maili Gönder</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Konu</label>
    <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($subject) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">İçerik</label>
    <textarea name="body" rows="10" class="form-control"><?= htmlspecialchars($body) ?></textarea>
  </div>
  <button type="submit" class="btn btn-primary">Gönder</button>
  <a href="service.php?id=<?= $svc['id'] ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

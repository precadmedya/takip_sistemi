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
$totalTl=0; $vatTl=0; $totalUsd=0; $vatUsd=0;
foreach($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $it){
    $sub=$it['unit_price']*$it['quantity'];
    $vat=$sub*$it['vat_rate']/100;
    $lineTotal=$sub+$vat;
    $isUsd = $it['currency']==='USD';
    $subTl = $isUsd ? $sub*$usdRate : $sub;
    $vatTlLine = $isUsd ? $vat*$usdRate : $vat;
    $lineTl = $isUsd ? $lineTotal*$usdRate : $lineTotal;
    $totalTl += $subTl;
    $vatTl += $vatTlLine;
    if($usdRate>0){
        $subUsd = $isUsd ? $sub : $sub/$usdRate;
        $vatUsdLine = $isUsd ? $vat : $vat/$usdRate;
        $lineUsd = $subUsd + $vatUsdLine;
        $totalUsd += $subUsd;
        $vatUsd += $vatUsdLine;
    } else {
        $lineUsd = 0;
    }
    $currencySymbol = $isUsd ? '$' : '₺';
    $rows.='<tr><td>'.htmlspecialchars($it['item_name']).'</td>';
    $rows.='<td>'.$it['quantity'].' '.htmlspecialchars($it['unit']).'</td>';
    $rows.='<td>'.number_format($it['unit_price'],2,',','.').' '.$currencySymbol.'</td>';
    $rows.='<td>'.$it['vat_rate'].'%</td>';
    $rows.='<td>'.number_format($vatTlLine,2,',','.').' ₺';
    if($usdRate>0){
        $rows.=' ('.number_format($isUsd ? $vat : $vatUsdLine,2,',','.').' $)';
    }
    $rows.='</td>';
    $rows.='<td>'.number_format($lineTl,2,',','.').' ₺';
    if($usdRate>0){
        $rows.=' ('.number_format($lineUsd,2,',','.').' $)';
    }
    $rows.='</td>';
    $rows.='<td>'.htmlspecialchars($svc['site_name']).'</td></tr>';
}
$grandTl=$totalTl+$vatTl;
$grandUsd=$usdRate>0 ? $totalUsd+$vatUsd : 0;
$settings=getEmailSettings($pdo);
$subject = 'Ödeme Hatırlatma';
$body = "Sayın {$svc['full_name']},<br><br>{$svc['site_name']} hizmetinizin bitiş tarihi yaklaşmaktadır. Bitiş tarihi: ".date('d.m.Y',strtotime($svc['due_date'])).".<br><br>";
$body .= '<table border="1" cellpadding="5" style="border-collapse:collapse">';
$body .= '<tr><th>Hizmet</th><th>Süre</th><th>Birim Fiyat</th><th>KDV Oranı</th><th>KDV Tutarı (TL / USD)</th><th>Toplam (TL / USD)</th><th>Site</th></tr>'.$rows.'</table>';
$body .= '<br><strong>Birim Fiyatı:</strong> '.number_format($totalTl,2,',','.').' ₺';
if($usdRate>0){
    $body .= ' ('.number_format($totalUsd,2,',','.').' $)';
}
$body .= '<br>';
$body .= '<strong>KDV Tutarı:</strong> '.number_format($vatTl,2,',','.').' ₺';
if($usdRate>0){
    $body .= ' ('.number_format($vatUsd,2,',','.').' $)';
}
$body .= '<br>';
$body .= '<strong>Genel Toplam:</strong> '.number_format($grandTl,2,',','.').' ₺';
if($usdRate>0){
    $body .= ' ('.number_format($grandUsd,2,',','.').' $)';
}
$body .= '<br><br>';
$body .= 'Hizmet süresini uzatmak veya ödeme yapmak için lütfen bizimle iletişime geçin.<br><br>Saygılarımızla,<br>'.$settings['from_name'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $body=$_POST['body'] ?? $body;
    $err='';
    sendMail($pdo,$svc['email'],$subject,$body,$err,$svc['id'],$svc['cid']);
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
    <input type="text" class="form-control" value="<?= htmlspecialchars($subject) ?>" readonly>
  </div>
  <div class="mb-3">
    <label class="form-label">İçerik</label>
    <textarea name="body" rows="10" class="form-control"><?= htmlspecialchars($body) ?></textarea>
  </div>
  <button type="submit" class="btn btn-primary">Gönder</button>
  <a href="service.php?id=<?= $svc['id'] ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

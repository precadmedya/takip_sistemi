<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT s.*, c.full_name, c.email, c.phone, c.company, c.address FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.id=?');
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$service){
    header('Location: services.php');
    exit;
}
$usdRate = getUsdRate($pdo);
$itemStmt = $pdo->prepare('SELECT si.*, pr.name AS provider_name FROM service_items si LEFT JOIN providers pr ON si.provider_id=pr.id WHERE si.service_id=?');
$itemStmt->execute([$service['id']]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0; $vatT=0;
foreach($items as $it){
    $line = $it['quantity']*$it['unit_price'];
    $lineVat = $line*$it['vat_rate']/100;
    if($it['currency']==='USD'){$line*=$usdRate; $lineVat*=$usdRate;}
    $total += $line; $vatT += $lineVat;
}
$grand = $total + $vatT;
$days = (strtotime($service['due_date']) - time())/86400;
$badge='success';
if($days<=30) $badge='info';
if($days<=14) $badge='warning';
if($days<0) $badge='danger';
include __DIR__.'/includes/header.php';
?>
<h1>Hizmet Detayı</h1>
<div class="row">
 <div class="col-md-8">
  <table class="table table-bordered">
   <tr><th>Müşteri</th><td><?= htmlspecialchars($service['full_name']) ?></td></tr>
   <tr><th>E-posta</th><td><?= htmlspecialchars($service['email']) ?></td></tr>
   <tr><th>Telefon</th><td><?= htmlspecialchars($service['phone']) ?></td></tr>
   <tr><th>Şirket</th><td><?= htmlspecialchars($service['company']) ?></td></tr>
   <tr><th>Site</th><td><?= htmlspecialchars($service['site_name']) ?></td></tr>
   <tr><th>Başlangıç Tarihi</th><td><?= date('d.m.Y',strtotime($service['start_date'])) ?></td></tr>
   <tr><th>Ödeme Tarihi</th><td><?= date('d.m.Y',strtotime($service['due_date'])) ?></td></tr>
   <tr><th>Durum</th><td><?= htmlspecialchars($service['status']) ?></td></tr>
   <tr><th>Not</th><td><?= nl2br(htmlspecialchars($service['notes'])) ?></td></tr>
  </table>
 </div>
 <div class="col-md-4 d-flex align-items-center justify-content-center">
  <span class="badge bg-<?= $badge ?> fs-4"><?= ($days>=0?'+':'').(int)$days ?> gün</span>
 </div>
</div>
<?php if($items): ?>
<h2>Hizmet / Ürün Detayı</h2>
<table class="table table-bordered">
 <thead>
  <tr>
   <th>Ad</th><th>Miktar</th><th>Birim</th><th>Birim Fiyat</th><th>Döviz</th><th>Sağlayıcı</th><th>KDV</th><th>Açıklama</th><th>Toplam (TL)</th>
  </tr>
 </thead>
 <tbody>
  <?php foreach($items as $it): ?>
  <?php $sub=$it['quantity']*$it['unit_price'];$vat=$sub*$it['vat_rate']/100;$line=$it['currency']=='USD'?($sub+$vat)*$usdRate:($sub+$vat); ?>
  <tr>
   <td><?= htmlspecialchars($it['item_name']) ?></td>
   <td><?= $it['quantity'] ?></td>
   <td><?= htmlspecialchars($it['unit']) ?></td>
   <td><?= number_format($it['unit_price'],2,',','.') ?></td>
   <td><?= htmlspecialchars($it['currency']) ?></td>
   <td><?= htmlspecialchars($it['provider_name']) ?></td>
   <td><?= $it['vat_rate'] ?>%</td>
   <td><?= htmlspecialchars($it['description']) ?></td>
   <td><?= number_format($line,2,',','.') ?></td>
  </tr>
  <?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>
<div class="text-end">
 <strong>Birim Fiyatı: <?= number_format($total,2,',','.') ?> TL</strong><br>
 <strong>KDV Tutarı: <?= number_format($vatT,2,',','.') ?> TL</strong><br>
 <strong>Genel Toplam: <?= number_format($grand,2,',','.') ?> TL</strong>
</div>
<a href="service_payment.php?service_id=<?= $service['id'] ?>" class="btn btn-primary">Tahsilat Yap</a>
<a href="send_reminder.php?service_id=<?= $service['id'] ?>" class="btn btn-info">Hatırlatma Maili Gönder</a>
<a href="service_edit.php?id=<?= $service['id'] ?>" class="btn btn-warning">Düzenle</a>
<a href="service_delete.php?id=<?= $service['id'] ?>" class="btn btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
<?php include __DIR__.'/includes/footer.php'; ?>

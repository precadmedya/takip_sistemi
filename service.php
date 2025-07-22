<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT s.*, c.full_name, p.name AS product_name, pr.name AS provider_name FROM services s JOIN customers c ON s.customer_id=c.id LEFT JOIN products p ON s.product_id=p.id LEFT JOIN providers pr ON s.provider_id=pr.id WHERE s.id=?');
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$service){
    header('Location: /services.php');
    exit;
}
$usdRate = getUsdRate($pdo);
$priceTl = $service['currency']==='USD' ? $service['price']*$usdRate : $service['price'];
$vatTl = $priceTl * $service['vat_rate']/100;
$grand = $priceTl + $vatTl;
$items = $pdo->prepare('SELECT * FROM service_items WHERE service_id=?');
$items->execute([$service['id']]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
include __DIR__.'/includes/header.php';
?>
<h1>Hizmet Detayı</h1>
<table class="table table-bordered">
<tr><th>Müşteri</th><td><?= htmlspecialchars($service['full_name']) ?></td></tr>
<tr><th>Ürün</th><td><?= htmlspecialchars($service['product_name']) ?></td></tr>
<tr><th>Sağlayıcı</th><td><?= htmlspecialchars($service['provider_name']) ?></td></tr>
<tr><th>Site</th><td><?= htmlspecialchars($service['site_name']) ?></td></tr>
<tr><th>Başlangıç</th><td><?= date('d.m.Y', strtotime($service['start_date'])) ?></td></tr>
<tr><th>Ödeme Tarihi</th><td><?= date('d.m.Y', strtotime($service['due_date'])) ?></td></tr>
<tr><th>Fiyat</th><td><?= number_format($service['price'],2,',','.') . ' ' . $service['currency'] ?></td></tr>
<tr><th>Fiyat TL</th><td><?= number_format($priceTl,2,',','.') ?> ₺</td></tr>
<tr><th>KDV</th><td><?= $service['vat_rate'] ?>%</td></tr>
<tr><th>Toplam Tutar (TL)</th><td><?= number_format($priceTl,2,',','.') ?> ₺</td></tr>
<tr><th>KDV Tutarı (TL)</th><td><?= number_format($vatTl,2,',','.') ?> ₺</td></tr>
<?php if($service['currency']==='USD'): ?>
<tr><th>Güncel Kur</th><td><?= number_format($usdRate,2,',','.') ?> ₺</td></tr>
<?php endif; ?>
<tr><th>Genel Toplam (TL)</th><td><?= number_format($grand,2,',','.') ?> ₺</td></tr>
<tr><th>Durum</th><td><?= htmlspecialchars($service['status']) ?></td></tr>
<tr><th>Not</th><td><?= nl2br(htmlspecialchars($service['notes'])) ?></td></tr>
</table>
<?php if($items): ?>
<h2>Hizmet / Ürün Detayı</h2>
<table class="table table-bordered">
 <thead>
  <tr>
   <th>Ad</th><th>Miktar</th><th>Birim</th><th>Birim Fiyat</th><th>KDV</th>
  </tr>
 </thead>
 <tbody>
  <?php foreach($items as $it): ?>
  <tr>
   <td><?= htmlspecialchars($it['item_name']) ?></td>
   <td><?= $it['quantity'] ?></td>
   <td><?= htmlspecialchars($it['unit']) ?></td>
   <td><?= number_format($it['unit_price'],2,',','.') ?></td>
   <td><?= $it['vat_rate'] ?>%</td>
  </tr>
  <?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>
<?php
$payStmt = $pdo->prepare('SELECT * FROM payments WHERE service_id=? ORDER BY id DESC');
$payStmt->execute([$service['id']]);
$payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Tahsilatlar</h2>
<table class="table table-bordered">
 <thead>
  <tr><th>Tutar</th><th>Para Birimi</th><th>Tarih</th><th>İşlem</th></tr>
 </thead>
 <tbody>
  <?php foreach($payments as $p): ?>
  <tr>
   <td><?= number_format($p['amount_orig'],2,',','.') ?></td>
   <td><?= htmlspecialchars($p['currency']) ?></td>
   <td><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
   <td>
    <a href="/payment_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
    <a href="/payment_delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
   </td>
  </tr>
  <?php endforeach; ?>
 </tbody>
</table>
<a href="/service_payment.php?service_id=<?= $service['id'] ?>" class="btn btn-primary">Tahsilat Yap</a>
<a href="/service_edit.php?id=<?= $service['id'] ?>" class="btn btn-warning">Düzenle</a>
<a href="/service_delete.php?id=<?= $service['id'] ?>" class="btn btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
<?php include __DIR__.'/includes/footer.php'; ?>

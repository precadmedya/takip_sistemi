<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$customer){
    header('Location: customers.php');
    exit;
}

$svcStmt = $pdo->prepare('SELECT s.*, p.name AS product_name, pr.name AS provider_name,
    IFNULL((SELECT SUM(amount_try) FROM payments WHERE service_id=s.id),0) AS paid_try
    FROM services s
    LEFT JOIN products p ON s.product_id=p.id
    LEFT JOIN providers pr ON s.provider_id=pr.id
    WHERE s.customer_id=? ORDER BY s.id DESC');
$svcStmt->execute([$id]);
$services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

$payStmt = $pdo->prepare('SELECT p.*, s.site_name FROM payments p
    LEFT JOIN services s ON p.service_id=s.id
    WHERE p.customer_id=? ORDER BY p.created_at DESC');
$payStmt->execute([$id]);
$payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

$usdRate = getUsdRate($pdo);
$totalDebt = 0;
$upcoming = [];
foreach($services as &$s){
    $s['total_try'] = $s['price_try'] * (1 + $s['vat_rate']/100);
    $s['remaining'] = $s['total_try'] - $s['paid_try'];
    if($s['remaining'] > 0){
        $totalDebt += $s['remaining'];
        if(strtotime($s['due_date']) <= strtotime('+30 days')){
            $upcoming[] = $s;
        }
    }
}
unset($s);

include __DIR__.'/includes/header.php';
?>
<h1><?= htmlspecialchars($customer['full_name']) ?></h1>
<p>E-Posta: <?= htmlspecialchars($customer['email']) ?></p>
<p>Telefon: <?= htmlspecialchars($customer['phone']) ?></p>
<p>Şirket: <?= htmlspecialchars($customer['company']) ?></p>
<p>Adres: <?= nl2br(htmlspecialchars($customer['address'])) ?></p>
<a href="customer_payment.php?customer_id=<?= $id ?>" class="btn btn-success mb-3">Tahsilat Yap</a>
<a href="customer_edit.php?id=<?= $id ?>" class="btn btn-warning mb-3">Düzenle</a>
<a href="customer_delete.php?id=<?= $id ?>" class="btn btn-danger mb-3" onclick="return confirm('Silinsin mi?');">Sil</a>
<a href="customer_statement.php?id=<?= $id ?>" class="btn btn-secondary mb-3">Ekstre İndir</a>
<div class="row mb-4">
 <div class="col-md-6">
  <div class="card text-bg-light mb-3">
   <div class="card-body">
    <h5 class="card-title">Toplam Borç</h5>
    <p class="card-text fw-bold"><?= number_format($totalDebt,2,',','.') ?> ₺</p>
   </div>
  </div>
 </div>
 <div class="col-md-6">
  <div class="card text-bg-light mb-3">
   <div class="card-body">
    <h5 class="card-title">Yaklaşan Ödemeler (30 gün)</h5>
    <?php if($upcoming): ?>
    <ul class="mb-0">
     <?php foreach($upcoming as $u): ?>
     <li><?= htmlspecialchars($u['site_name']) ?> - <?= date('d.m.Y', strtotime($u['due_date'])) ?> - <?= number_format($u['remaining'],2,',','.') ?> ₺</li>
     <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="mb-0">Yaklaşan ödeme yok</p>
    <?php endif; ?>
   </div>
  </div>
 </div>
</div>
<h2>Hizmetleri</h2>
<table class="table table-bordered">
 <thead>
  <tr>
   <th>Ürün</th><th>Site</th><th>Ödeme Tarihi</th><th>Fiyat</th><th>Ödenen</th><th>Kalan</th><th>İşlem</th>
  </tr>
 </thead>
 <tbody>
 <?php foreach($services as $s): ?>
  <tr>
   <td><?= htmlspecialchars($s['product_name']) ?></td>
   <td><?= htmlspecialchars($s['site_name']) ?></td>
   <td><?= date('d.m.Y', strtotime($s['due_date'])) ?></td>
   <td><?= number_format($s['total_try'],2,',','.') ?> ₺</td>
   <td><?= number_format($s['paid_try'],2,',','.') ?> ₺</td>
   <td><?= number_format($s['remaining'],2,',','.') ?> ₺</td>
   <td>
    <a href="service.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">Detay</a>
    <a href="service_payment.php?service_id=<?= $s['id'] ?>" class="btn btn-sm btn-primary">Tahsilat</a>
    <a href="service_edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
    <a href="service_delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
   </td>
  </tr>
 <?php endforeach; ?>
 </tbody>
</table>
<h2>Tahsilatlar</h2>
<table class="table table-bordered">
 <thead>
  <tr><th>Hizmet/Site</th><th>Tutar (TL)</th><th>Para Birimi</th><th>Tarih</th></tr>
 </thead>
 <tbody>
  <?php foreach($payments as $p): ?>
  <tr>
   <td><?= htmlspecialchars($p['site_name']) ?></td>
   <td><?= number_format($p['amount_try'],2,',','.') ?> ₺</td>
   <td><?= htmlspecialchars($p['currency']) ?></td>
   <td><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
  </tr>
  <?php endforeach; ?>
 </tbody>
</table>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$customer){
    header('Location: /customers.php');
    exit;
}

$services = $pdo->prepare('SELECT s.*, p.name AS product_name, pr.name AS provider_name
    FROM services s
    LEFT JOIN products p ON s.product_id=p.id
    LEFT JOIN providers pr ON s.provider_id=pr.id
    WHERE s.customer_id=? ORDER BY s.id DESC');
$services->execute([$id]);
$services = $services->fetchAll(PDO::FETCH_ASSOC);
$usdRate = getUsdRate($pdo);

include __DIR__.'/includes/header.php';
?>
<h1><?= htmlspecialchars($customer['full_name']) ?></h1>
<p>E-Posta: <?= htmlspecialchars($customer['email']) ?></p>
<p>Telefon: <?= htmlspecialchars($customer['phone']) ?></p>
<p>Şirket: <?= htmlspecialchars($customer['company']) ?></p>
<p>Adres: <?= nl2br(htmlspecialchars($customer['address'])) ?></p>
<a href="/customer_payment.php?customer_id=<?= $id ?>" class="btn btn-success mb-3">Tahsilat Yap</a>
<h2>Hizmetleri</h2>
<table class="table table-bordered">
 <thead>
  <tr>
   <th>Ürün</th><th>Site</th><th>Başlangıç</th><th>Ödeme Tarihi</th><th>Fiyat</th><th>Fiyat TL</th><th>KDV</th><th>Genel Toplam</th><th>İşlem</th>
  </tr>
 </thead>
 <tbody>
 <?php foreach($services as $s): ?>
  <tr>
   <td><?= htmlspecialchars($s['product_name']) ?></td>
   <td><?= htmlspecialchars($s['site_name']) ?></td>
   <td><?= date('d.m.Y', strtotime($s['start_date'])) ?></td>
   <td><?= date('d.m.Y', strtotime($s['due_date'])) ?></td>
   <td><?= number_format($s['price'],2,',','.') . ' ' . $s['currency'] ?></td>
   <td><?= number_format($s['price_try'],2,',','.') ?> ₺</td>
   <td><?= $s['vat_rate'] ?>%</td>
   <td><?= number_format($s['price_try'] * (1+$s['vat_rate']/100), 2, ',', '.') ?> ₺</td>
   <td>
     <a href="/service.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">Detay</a>
     <a href="/service_payment.php?service_id=<?= $s['id'] ?>" class="btn btn-sm btn-primary">Tahsilat</a>
   </td>
  </tr>
 <?php endforeach; ?>
 </tbody>
</table>
<?php include __DIR__.'/includes/footer.php'; ?>

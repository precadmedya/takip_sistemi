<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
include __DIR__.'/includes/header.php';

$stmt = $pdo->query("SELECT s.*, c.full_name FROM services s JOIN customers c ON s.customer_id=c.id ORDER BY s.id DESC");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$usdRate = getUsdRate($pdo);
?>
<h1>Hizmetler</h1>
<a href="service_add.php" class="btn btn-primary mb-3">Hizmet Ekle</a>
<table class="table table-bordered">
  <thead>
    <tr>
       <th>ID</th>
       <th>Müşteri</th>
       <th>Hizmet Türü</th>
       <th>Site</th>
       <th>Başlangıç</th>
       <th>Ödeme Tarihi</th>
       <th>Fiyat</th>
       <th>Fiyat TL</th>
       <th>KDV</th>
       <th>Genel Toplam</th>
       <th>Durum</th>
       <th>Not</th>
       <th>Oluşturma</th>
       <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($services as $s): ?>
    <tr>
      <td><?= $s['id'] ?></td>
      <td><?= htmlspecialchars($s['full_name']) ?></td>
      <td><?= htmlspecialchars($s['service_type']) ?></td>
      <td><?= htmlspecialchars($s['site_name']) ?></td>
      <td><?= date('d.m.Y', strtotime($s['start_date'])) ?></td>
      <td><?= date('d.m.Y', strtotime($s['due_date'])) ?></td>
      <td><?= number_format($s['price'],2,',','.') . ' ' . $s['currency'] ?></td>
      <td><?= number_format($s['price_try'],2,',','.') ?> ₺</td>
      <td><?= $s['vat_rate'] ?>%</td>
      <td><?= number_format($s['price_try'] * (1 + $s['vat_rate']/100), 2, ',', '.') ?> ₺</td>
      <td><?= htmlspecialchars($s['status']) ?></td>
      <td><?= htmlspecialchars($s['notes']) ?></td>
      <td><?= date('d.m.Y', strtotime($s['created_at'])) ?></td>
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
<?php include __DIR__.'/includes/footer.php'; ?>

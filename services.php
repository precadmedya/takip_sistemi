<?php
require __DIR__.'/includes/auth.php';
include __DIR__.'/includes/header.php';

$stmt = $pdo->query("SELECT s.*, c.full_name, p.name AS product_name, pr.name AS provider_name FROM services s JOIN customers c ON s.customer_id=c.id LEFT JOIN products p ON s.product_id=p.id LEFT JOIN providers pr ON s.provider_id=pr.id ORDER BY s.id DESC");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Hizmetler</h1>
<a href="/service_add.php" class="btn btn-primary mb-3">Hizmet Ekle</a>
<table class="table table-bordered">
  <thead>
    <tr>
       <th>ID</th>
       <th>Müşteri</th>
       <th>Ürün</th>
       <th>Sağlayıcı</th>
       <th>Site</th>
       <th>Tip</th>
       <th>Başlangıç</th>
       <th>Süre</th>
       <th>Birim</th>
       <th>Fiyat</th>
       <th>Döviz</th>
       <th>KDV</th>
       <th>TL Tutar</th>
       <th>Durum</th>
       <th>Not</th>
       <th>Oluşturma</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($services as $s): ?>
    <tr>
      <td><?= $s['id'] ?></td>
      <td><?= htmlspecialchars($s['full_name']) ?></td>
      <td><?= htmlspecialchars($s['product_name']) ?></td>
      <td><?= htmlspecialchars($s['provider_name']) ?></td>
      <td><?= htmlspecialchars($s['site_name']) ?></td>
      <td><?= htmlspecialchars($s['service_type']) ?></td>
      <td><?= date('d.m.Y', strtotime($s['start_date'])) ?></td>
      <td><?= $s['duration'] ?></td>
      <td><?= htmlspecialchars($s['unit']) ?></td>
      <td><?= $s['price'] ?></td>
      <td><?= htmlspecialchars($s['currency']) ?></td>
      <td><?= $s['vat_rate'] ?></td>
      <td><?= $s['price_try'] ?></td>
      <td><?= htmlspecialchars($s['status']) ?></td>
      <td><?= htmlspecialchars($s['notes']) ?></td>
      <td><?= date('d.m.Y', strtotime($s['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__.'/includes/footer.php'; ?>

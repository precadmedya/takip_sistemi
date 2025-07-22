<?php
require __DIR__.'/includes/auth.php';
include __DIR__.'/includes/header.php';

$stmt = $pdo->query("SELECT c.*, 
    IFNULL(SUM(s.price_try * (1 + s.vat_rate/100)),0) - 
    IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.customer_id=c.id),0) AS balance
    FROM customers c
    LEFT JOIN services s ON s.customer_id = c.id
    GROUP BY c.id
    ORDER BY c.id DESC");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Müşteriler</h1>
<a href="customer_add.php" class="btn btn-primary mb-3">Müşteri Ekle</a>
<table class="table table-bordered">
  <thead>
    <tr>
       <th>ID</th>
       <th>Ad Soyad</th>
       <th>E-Posta</th>
       <th>Telefon</th>
       <th>Şirket</th>
       <th>Adres</th>
       <th>Bakiye (TL)</th>
       <th>Oluşturma</th>
       <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['id']) ?></td>
      <td><?= htmlspecialchars($c['full_name']) ?></td>
      <td><?= htmlspecialchars($c['email']) ?></td>
      <td><?= htmlspecialchars($c['phone']) ?></td>
      <td><?= htmlspecialchars($c['company']) ?></td>
      <td><?= htmlspecialchars($c['address']) ?></td>
      <td><?= number_format($c['balance'], 2, ',', '.') ?> ₺</td>
      <td><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
      <td>
        <a href="customer.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info">Detay</a>
        <a href="customer_payment.php?customer_id=<?= $c['id'] ?>" class="btn btn-sm btn-success">Tahsilat</a>
        <a href="customer_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="customer_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/db.php';
include __DIR__.'/includes/header.php';

$stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Müşteriler</h1>
<a href="/customer_add.php" class="btn btn-primary mb-3">Müşteri Ekle</a>
<table class="table table-bordered">
  <thead>
    <tr>
       <th>ID</th>
       <th>Ad Soyad</th>
       <th>E-Posta</th>
       <th>Telefon</th>
       <th>Şirket</th>
       <th>Adres</th>
       <th>Oluşturma</th>
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
      <td><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__.'/includes/footer.php'; ?>

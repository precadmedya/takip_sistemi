<?php
require __DIR__ . '/includes/auth.php';

$stmt = $pdo->query('SELECT * FROM exchange_rates ORDER BY rate_date DESC');
$rates = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<h1>Kur Bilgisi</h1>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>Tarih</th>
      <th>USD/TRY</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rates as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['rate_date']) ?></td>
      <td><?= number_format($r['usd_try'], 4, ',', '.') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/includes/footer.php'; ?>

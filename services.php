<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

// CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=services.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','customer_id','service_type','site_name','start_date','due_date','price','currency','vat_rate','price_try','status','notes']);
    $stmt = $pdo->query('SELECT id, customer_id, service_type, site_name, start_date, due_date, price, currency, vat_rate, price_try, status, notes FROM services ORDER BY id');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// CSV import
if (isset($_POST['import']) && isset($_FILES['import_file']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
    $file = fopen($_FILES['import_file']['tmp_name'], 'r');
    $header = fgetcsv($file);
    $count = 0;
    if ($header) {
        $usdRate = getUsdRate($pdo);
        while (($data = fgetcsv($file)) !== false) {
            $row = array_combine($header, $data);
            if (!$row) continue;
            $customerId = $row['customer_id'] ?? null;
            if (!$customerId) continue;
            $price = (float)($row['price'] ?? 0);
            $currency = $row['currency'] ?? 'TRY';
            $vat = (float)($row['vat_rate'] ?? 0);
            $priceTry = $row['price_try'] ?? null;
            if ($priceTry === null || $priceTry === '') {
                $priceTry = $currency === 'USD' ? $price * $usdRate : $price;
                $priceTry += $priceTry * $vat / 100;
            }
            $duration = 0;
            if (!empty($row['start_date']) && !empty($row['due_date'])) {
                $duration = (int)((strtotime($row['due_date']) - strtotime($row['start_date'])) / 86400);
            }
            $id = $row['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT id FROM services WHERE id=?');
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE services SET customer_id=?, service_type=?, site_name=?, start_date=?, due_date=?, duration=?, unit=?, price=?, currency=?, vat_rate=?, price_try=?, status=?, notes=? WHERE id=?');
                    $u->execute([$customerId, $row['service_type'], $row['site_name'], $row['start_date'], $row['due_date'], $duration, 'gün', $price, $currency, $vat, $priceTry, $row['status'], $row['notes'], $id]);
                    $count++;
                    continue;
                }
            }
            $i = $pdo->prepare("INSERT INTO services (customer_id, product_id, provider_id, site_name, service_type, start_date, due_date, duration, unit, price, currency, vat_rate, price_try, status, notes, reminder_enabled, reminder_days, created_at) VALUES (?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, '', NOW())");
            $i->execute([$customerId, $row['site_name'], $row['service_type'], $row['start_date'], $row['due_date'], $duration, 'gün', $price, $currency, $vat, $priceTry, $row['status'], $row['notes']]);
            $count++;
        }
    }
    fclose($file);
    $_SESSION['message'] = $count.' kayıt içeri aktarıldı';
    header('Location: services.php');
    exit;
}

include __DIR__.'/includes/header.php';

$stmt = $pdo->query("SELECT s.*, c.full_name FROM services s JOIN customers c ON s.customer_id=c.id ORDER BY s.due_date ASC");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Hizmetler</h1>
<a href="service_add.php" class="btn btn-primary mb-3">Hizmet Ekle</a>
<a href="services.php?export=1" class="btn btn-secondary mb-3 ms-2">CSV Dışa Aktar</a>
<form method="post" enctype="multipart/form-data" class="d-inline-block mb-3 ms-2">
  <input type="file" name="import_file" accept=".csv" required class="form-control d-inline-block" style="width:auto;">
  <button type="submit" name="import" class="btn btn-secondary">CSV İçe Aktar</button>
</form>
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

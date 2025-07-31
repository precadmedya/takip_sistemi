<?php
require __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

// CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=customers.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','full_name','email','phone','company','address']);
    $stmt = $pdo->query('SELECT id, full_name, email, phone, company, address FROM customers ORDER BY id');
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
        while (($data = fgetcsv($file)) !== false) {
            $row = array_combine($header, $data);
            if (!$row) continue;
            $email = trim($row['email'] ?? '');
            $id = $row['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT id FROM customers WHERE id=?');
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE customers SET full_name=?, email=?, phone=?, company=?, address=? WHERE id=?');
                    $u->execute([$row['full_name'],$email,$row['phone'],$row['company'],$row['address'],$id]);
                    $count++; continue;
                }
            }
            if ($email) {
                $stmt = $pdo->prepare('SELECT id FROM customers WHERE email=?');
                $stmt->execute([$email]);
                if ($cid = $stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE customers SET full_name=?, phone=?, company=?, address=? WHERE id=?');
                    $u->execute([$row['full_name'],$row['phone'],$row['company'],$row['address'],$cid]);
                    $count++; continue;
                }
            }
            $i = $pdo->prepare('INSERT INTO customers(full_name,email,phone,company,address) VALUES (?,?,?,?,?)');
            $i->execute([$row['full_name'],$email,$row['phone'],$row['company'],$row['address']]);
            $count++;
        }
    }
    fclose($file);
    $_SESSION['message'] = $count.' kayıt içeri aktarıldı';
    header('Location: customers.php');
    exit;
}

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
<a href="customers.php?export=1" class="btn btn-secondary mb-3 ms-2">CSV Dışa Aktar</a>
<form method="post" enctype="multipart/form-data" class="d-inline-block mb-3 ms-2">
  <input type="file" name="import_file" accept=".csv" required class="form-control d-inline-block" style="width:auto;">
  <button type="submit" name="import" class="btn btn-secondary">CSV İçe Aktar</button>
</form>
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

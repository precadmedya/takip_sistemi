<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=products.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','name','unit','vat_rate','price','currency']);
    $sql = 'SELECT id,name,unit,vat_rate,price,currency FROM products';
    $params=[];
    if(!isAdmin()){ $sql .= ' WHERE user_id=?'; $params[] = currentUserId(); }
    $sql .= ' ORDER BY id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
            $name = trim($row['name'] ?? '');
            $id = $row['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT id FROM products WHERE id=?');
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE products SET name=?, unit=?, vat_rate=?, price=?, currency=? WHERE id=?');
                    $u->execute([$row['name'],$row['unit'],$row['vat_rate'],$row['price'],$row['currency'],$id]);
                    $count++; continue;
                }
            }
            if ($name) {
                $stmt = $pdo->prepare('SELECT id FROM products WHERE name=?');
                $stmt->execute([$name]);
                if ($pid = $stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE products SET unit=?, vat_rate=?, price=?, currency=? WHERE id=?');
                    $u->execute([$row['unit'],$row['vat_rate'],$row['price'],$row['currency'],$pid]);
                    $count++; continue;
                }
            }
            $i = $pdo->prepare('INSERT INTO products(name,unit,vat_rate,price,currency,user_id) VALUES (?,?,?,?,?,?)');
            $i->execute([$row['name'],$row['unit'],$row['vat_rate'],$row['price'],$row['currency'],currentUserId()]);
            $count++;
        }
    }
    fclose($file);
    $_SESSION['message'] = $count.' kayıt içeri aktarıldı';
    header('Location: products.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
    $stmt->execute([$id]);
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $unit = $_POST['unit'];
    $vat_rate = $_POST['vat_rate'];
    $price = $_POST['price'];
    $currency = $_POST['currency'];
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare('UPDATE products SET name=?, unit=?, vat_rate=?, price=?, currency=? WHERE id=?');
        $stmt->execute([$name, $unit, $vat_rate, $price, $currency, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO products (name, unit, vat_rate, price, currency, user_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $unit, $vat_rate, $price, $currency, currentUserId()]);
    }
    header('Location: products.php');
    exit;
}

$edit = null;
if ($action === 'edit' && $id) {
    $sql='SELECT * FROM products WHERE id=?';
    $params=[$id];
    if(!isAdmin()){ $sql.=' AND user_id=?'; $params[] = currentUserId(); }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$prodSql = 'SELECT * FROM products';
if(!isAdmin()){ $prodSql .= ' WHERE user_id='.currentUserId(); }
$prodSql .= ' ORDER BY name ASC';
$products = $pdo->query($prodSql)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<h1>Ürünler</h1>
<a href="products.php?export=1" class="btn btn-secondary mb-3">CSV Dışa Aktar</a>
<form method="post" enctype="multipart/form-data" class="d-inline-block mb-3 ms-2">
  <input type="file" name="import_file" accept=".csv" required class="form-control d-inline-block" style="width:auto;">
  <button type="submit" name="import" class="btn btn-secondary">CSV İçe Aktar</button>
</form>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>ID</th>
      <th>Ad</th>
      <th>Birim</th>
      <th>KDV</th>
      <th>Fiyat</th>
      <th>Döviz</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td><?= htmlspecialchars($p['unit']) ?></td>
      <td><?= $p['vat_rate'] ?></td>
      <td><?= $p['price'] ?></td>
      <td><?= htmlspecialchars($p['currency']) ?></td>
      <td>
        <a href="products.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="products.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<hr>
<h2><?= $edit ? 'Ürünü Düzenle' : 'Yeni Ürün' ?></h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Ürün Adı</label>
    <input type="text" name="name" class="form-control" value="<?= $edit['name'] ?? '' ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Birim</label>
    <select name="unit" class="form-control">
      <option value="yıl" <?= isset($edit) && $edit['unit']==='yıl' ? 'selected' : '' ?>>Yıl</option>
      <option value="ay" <?= isset($edit) && $edit['unit']==='ay' ? 'selected' : '' ?>>Ay</option>
      <option value="adet" <?= isset($edit) && $edit['unit']==='adet' ? 'selected' : '' ?>>Adet</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">KDV</label>
    <select name="vat_rate" class="form-control">
      <option value="0" <?= isset($edit) && $edit['vat_rate']==0 ? 'selected' : '' ?>>Yok</option>
      <option value="10" <?= isset($edit) && $edit['vat_rate']==10 ? 'selected' : '' ?>>%10</option>
      <option value="20" <?= isset($edit) && $edit['vat_rate']==20 ? 'selected' : '' ?>>%20</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Fiyat</label>
    <input type="text" name="price" class="form-control" value="<?= $edit['price'] ?? '' ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Fiyat Türü</label>
    <select name="currency" class="form-control">
      <option value="TRY" <?= isset($edit) && $edit['currency']==='TRY' ? 'selected' : '' ?>>TL</option>
      <option value="USD" <?= isset($edit) && $edit['currency']==='USD' ? 'selected' : '' ?>>USD</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

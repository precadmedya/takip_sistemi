<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=products.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','name','unit','vat_rate','price','currency']);
    if($_SESSION['role']==='admin') {
        $stmt = $pdo->query('SELECT id,name,unit,vat_rate,price,currency FROM products ORDER BY id');
    } else {
        $stmt = $pdo->prepare('SELECT id,name,unit,vat_rate,price,currency FROM products WHERE user_id=? ORDER BY id');
        $stmt->execute([$_SESSION['user_id']]);
    }
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
                $stmt = $pdo->prepare('SELECT id FROM products WHERE id=? AND user_id=?');
                $stmt->execute([$id, $_SESSION['user_id']]);
                if ($stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE products SET name=?, unit=?, vat_rate=?, price=?, currency=? WHERE id=? AND user_id=?');
                    $u->execute([$row['name'],$row['unit'],$row['vat_rate'],$row['price'],$row['currency'],$id,$_SESSION['user_id']]);
                    $count++; continue;
                }
            }
            if ($name) {
                $stmt = $pdo->prepare('SELECT id FROM products WHERE name=? AND user_id=?');
                $stmt->execute([$name, $_SESSION['user_id']]);
                if ($pid = $stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE products SET unit=?, vat_rate=?, price=?, currency=? WHERE id=? AND user_id=?');
                    $u->execute([$row['unit'],$row['vat_rate'],$row['price'],$row['currency'],$pid,$_SESSION['user_id']]);
                    $count++; continue;
                }
            }
            $i = $pdo->prepare('INSERT INTO products(name,unit,vat_rate,price,currency,user_id) VALUES (?,?,?,?,?,?)');
            $i->execute([$row['name'],$row['unit'],$row['vat_rate'],$row['price'],$row['currency'],$_SESSION['user_id']]);
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
    $sql = 'DELETE FROM products WHERE id=?';
    $params = [$id];
    if($_SESSION['role'] !== 'admin') {
        $sql .= ' AND user_id=?';
        $params[] = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
        $sql = 'UPDATE products SET name=?, unit=?, vat_rate=?, price=?, currency=? WHERE id=?';
        $params = [$name, $unit, $vat_rate, $price, $currency, $id];
        if($_SESSION['role'] !== 'admin') {
            $sql .= ' AND user_id=?';
            $params[] = $_SESSION['user_id'];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare('INSERT INTO products (name, unit, vat_rate, price, currency, user_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $unit, $vat_rate, $price, $currency, $_SESSION['user_id']]);
    }
    header('Location: products.php');
    exit;
}

$edit = null;
if ($action === 'edit' && $id) {
    $sql = 'SELECT * FROM products WHERE id=?';
    $params = [$id];
    if($_SESSION['role'] !== 'admin') {
        $sql .= ' AND user_id=?';
        $params[] = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

if($_SESSION['role'] === 'admin') {
    $products = $pdo->query('SELECT * FROM products ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE user_id=? ORDER BY name ASC');
    $stmt->execute([$_SESSION['user_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

<?php
require __DIR__ . '/includes/auth.php';

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
        $stmt = $pdo->prepare('INSERT INTO products (name, unit, vat_rate, price, currency) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $unit, $vat_rate, $price, $currency]);
    }
    header('Location: products.php');
    exit;
}

$edit = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
    $stmt->execute([$id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<h1>Ürünler</h1>
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

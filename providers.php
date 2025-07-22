<?php
require __DIR__ . '/includes/auth.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare('DELETE FROM providers WHERE id=?');
    $stmt->execute([$id]);
    header('Location: providers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $website = $_POST['website'];
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare('UPDATE providers SET name=?, website=? WHERE id=?');
        $stmt->execute([$name, $website, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO providers (name, website) VALUES (?, ?)');
        $stmt->execute([$name, $website]);
    }
    header('Location: providers.php');
    exit;
}

$edit = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM providers WHERE id=?');
    $stmt->execute([$id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query('SELECT p.*, (SELECT SUM(price_try) FROM provider_purchases WHERE provider_id=p.id) AS total_due FROM providers p ORDER BY p.id DESC');
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<h1>Sağlayıcılar</h1>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>ID</th>
      <th>Ad</th>
      <th>Web Sitesi</th>
      <th>Borç (TL)</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($providers as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><a href="provider.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
      <td><?= htmlspecialchars($p['website']) ?></td>
      <td><?= number_format($p['total_due'] ?? 0, 2, ',', '.') ?></td>
      <td>
        <a href="providers.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="providers.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<hr>
<h2><?= $edit ? 'Sağlayıcıyı Düzenle' : 'Yeni Sağlayıcı' ?></h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Ad</label>
    <input type="text" name="name" class="form-control" value="<?= $edit['name'] ?? '' ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Web Sitesi</label>
    <input type="text" name="website" class="form-control" value="<?= $edit['website'] ?? '' ?>">
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

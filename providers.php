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
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare('UPDATE providers SET name=? WHERE id=?');
        $stmt->execute([$name, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO providers (name) VALUES (?)');
        $stmt->execute([$name]);
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

$providers = $pdo->query('SELECT * FROM providers ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<h1>Sağlayıcılar</h1>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>ID</th>
      <th>Ad</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($providers as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><a href="provider.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
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
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require __DIR__ . '/includes/auth.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

if ($action === 'delete' && $id) {
    $sql = 'DELETE FROM providers WHERE id=?';
    $params = [$id];
    if($_SESSION['role'] !== 'admin') {
        $sql .= ' AND user_id=?';
        $params[] = $_SESSION['user_id'];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    header('Location: providers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $website = $_POST['website'];
    if ($action === 'edit' && $id) {
        $sql = 'UPDATE providers SET name=?, website=? WHERE id=?';
        $params = [$name, $website, $id];
        if($_SESSION['role'] !== 'admin') {
            $sql .= ' AND user_id=?';
            $params[] = $_SESSION['user_id'];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare('INSERT INTO providers (name, website, user_id) VALUES (?, ?, ?)');
        $stmt->execute([$name, $website, $_SESSION['user_id']]);
    }
    header('Location: providers.php');
    exit;
}

$edit = null;
if ($action === 'edit' && $id) {
    $sql = 'SELECT * FROM providers WHERE id=?';
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
    $stmt = $pdo->query('SELECT p.*, (SELECT IFNULL(SUM(price_try),0) FROM provider_purchases WHERE provider_id=p.id) - (SELECT IFNULL(SUM(amount_try),0) FROM provider_payments WHERE provider_id=p.id) AS total_due FROM providers p ORDER BY p.id DESC');
} else {
    $stmt = $pdo->prepare('SELECT p.*, (SELECT IFNULL(SUM(price_try),0) FROM provider_purchases WHERE provider_id=p.id AND user_id=?) - (SELECT IFNULL(SUM(amount_try),0) FROM provider_payments WHERE provider_id=p.id AND user_id=?) AS total_due FROM providers p WHERE p.user_id=? ORDER BY p.id DESC');
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
}
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
        <a href="provider_payment.php?provider_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Ödeme Yap</a>
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

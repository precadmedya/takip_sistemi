<?php
require __DIR__ . '/includes/auth.php';

$logo = '';
$stmt = $pdo->query("SELECT value FROM settings WHERE `key`='logo'");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $logo = $row['value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['logo']['tmp_name'])) {
        $dir = 'uploads';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/' . basename($_FILES['logo']['name']);
        move_uploaded_file($_FILES['logo']['tmp_name'], $path);
        $stmt = $pdo->prepare("REPLACE INTO settings (`key`, value) VALUES ('logo', ?)");
        $stmt->execute([$path]);
        $logo = $path;
    }
}

include __DIR__ . '/includes/header.php';
?>
<h1>Logo Ayarları</h1>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Logo Yükle</label>
    <input type="file" name="logo" class="form-control">
  </div>
  <?php if ($logo): ?>
  <div class="mb-3">
    <img src="/<?= htmlspecialchars($logo) ?>" alt="Logo" style="max-width:200px;">
  </div>
  <?php endif; ?>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

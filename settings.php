<?php
require __DIR__ . '/includes/auth.php';

$settings = [];
$keys = ['logo','logo_login_width','logo_login_height','logo_header_width','logo_header_height'];
foreach ($keys as $k) {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->execute([$k]);
    $settings[$k] = $stmt->fetchColumn() ?: '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $save_message = '';
    if (!empty($_FILES['logo']['tmp_name'])) {
        $dir = 'uploads';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/' . basename($_FILES['logo']['name']);
        move_uploaded_file($_FILES['logo']['tmp_name'], $path);
        $stmt = $pdo->prepare("REPLACE INTO settings (`key`, value) VALUES ('logo', ?)");
        $stmt->execute([$path]);
        $settings['logo'] = $path;
    }
    foreach (['logo_login_width','logo_login_height','logo_header_width','logo_header_height'] as $k) {
        if (isset($_POST[$k])) {
            $stmt = $pdo->prepare("REPLACE INTO settings (`key`, value) VALUES (?, ?)");
            $stmt->execute([$k, $_POST[$k]]);
            $settings[$k] = $_POST[$k];
        }
    }
    $save_message = 'Ayarlar kaydedildi';
}

include __DIR__ . '/includes/header.php';
?>
<h1>Genel Ayarlar</h1>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Logo Yükle</label>
    <input type="file" name="logo" class="form-control">
  </div>
  <?php if ($settings['logo']): ?>
  <div class="mb-3">
    <img src="/<?= htmlspecialchars($settings['logo']) ?>" alt="Logo" style="max-width:200px;">
  </div>
  <?php endif; ?>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Login Logo Genişlik</label>
      <input type="number" name="logo_login_width" class="form-control" value="<?= htmlspecialchars($settings['logo_login_width']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Login Logo Yükseklik</label>
      <input type="number" name="logo_login_height" class="form-control" value="<?= htmlspecialchars($settings['logo_login_height']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Header Logo Genişlik</label>
      <input type="number" name="logo_header_width" class="form-control" value="<?= htmlspecialchars($settings['logo_header_width']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Header Logo Yükseklik</label>
      <input type="number" name="logo_header_height" class="form-control" value="<?= htmlspecialchars($settings['logo_header_height']) ?>">
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php if(!empty($save_message)): ?>
<div class="alert alert-success mt-3">
  <?= $save_message ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

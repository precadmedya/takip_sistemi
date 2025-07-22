<?php
require __DIR__ . '/includes/auth.php';

$settings = [];
$keys = ['logo','logo_login_width','logo_login_height','logo_header_width','logo_header_height',
    'smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email'];
foreach ($keys as $k) {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->execute([$k]);
    $settings[$k] = $stmt->fetchColumn() ?: '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['logo']['tmp_name'])) {
        $dir = 'uploads';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/' . basename($_FILES['logo']['name']);
        move_uploaded_file($_FILES['logo']['tmp_name'], $path);
        $stmt = $pdo->prepare("REPLACE INTO settings (`key`, value) VALUES ('logo', ?)");
        $stmt->execute([$path]);
        $settings['logo'] = $path;
    }
    foreach (['logo_login_width','logo_login_height','logo_header_width','logo_header_height','smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email'] as $k) {
        if (isset($_POST[$k])) {
            $stmt = $pdo->prepare("REPLACE INTO settings (`key`, value) VALUES (?, ?)");
            $stmt->execute([$k, $_POST[$k]]);
            $settings[$k] = $_POST[$k];
        }
    }
    if(isset($_POST['send_test']) && !empty($_POST['test_email'])){
        require_once __DIR__.'/includes/functions.php';
        $sent = sendMail($pdo, $_POST['test_email'], 'Test', 'Bu bir test e-postasıdır.');
        $test_message = $sent ? 'Test maili gönderildi' : 'Gönderim başarısız';
    }
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
  <h2 class="mt-4">SMTP Ayarları</h2>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Sunucu</label>
      <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host']) ?>">
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Port</label>
      <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port']) ?>">
    </div>
    <div class="col-md-3 mb-3">
      <label class="form-label">Şifreleme</label>
      <select name="smtp_encryption" class="form-control">
        <option value="ssl" <?= $settings['smtp_encryption']==='ssl'?'selected':'' ?>>SSL</option>
        <option value="tls" <?= $settings['smtp_encryption']==='tls'?'selected':'' ?>>TLS</option>
      </select>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Kullanıcı Adı</label>
      <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Şifre</label>
      <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Gönderen Adı</label>
      <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_name']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Gönderen E-Posta</label>
      <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_email']) ?>">
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<h2 class="mt-4">Mail Testi</h2>
<?php if(!empty($test_message)): ?>
<div class="alert alert-info"><?= $test_message ?></div>
<?php endif; ?>
<form method="post">
  <div class="input-group mb-3">
    <input type="email" name="test_email" class="form-control" placeholder="E-posta" required>
    <button type="submit" name="send_test" value="1" class="btn btn-secondary">Test Mail Gönder</button>
  </div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
require __DIR__ . '/includes/auth.php';

$settings = [];
$keys = ['logo','logo_login_width','logo_login_height','logo_header_width','logo_header_height'];
if ($_SESSION['role'] === 'admin') {
    $keys = array_merge($keys, ['footer_text','footer_logo','footer_logo_width','footer_logo_height']);
}
foreach ($keys as $k) {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key`=? AND (user_id IS NULL OR user_id=?) ORDER BY user_id DESC LIMIT 1');
    $stmt->execute([$k, $_SESSION['user_id']]);
    $settings[$k] = $stmt->fetchColumn() ?: '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $save_message = '';
    $uid = $_SESSION['role'] === 'admin' ? null : $_SESSION['user_id'];
    if (!empty($_FILES['logo']['tmp_name'])) {
        $dir = 'uploads';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/' . basename($_FILES['logo']['name']);
        move_uploaded_file($_FILES['logo']['tmp_name'], $path);
        $stmt = $pdo->prepare("REPLACE INTO settings (`key`, user_id, value) VALUES ('logo', ?, ?)");
        $stmt->execute([$uid, $path]);
        $settings['logo'] = $path;
    }
    if($_SESSION['role']==='admin' && !empty($_FILES['footer_logo']['tmp_name'])){
        $dir = 'uploads';
        if(!is_dir($dir)) mkdir($dir,0777,true);
        $path = $dir.'/'.basename($_FILES['footer_logo']['name']);
        move_uploaded_file($_FILES['footer_logo']['tmp_name'],$path);
        $stmt = $pdo->prepare("REPLACE INTO settings (`key`,user_id,value) VALUES ('footer_logo',NULL,?)");
        $stmt->execute([$path]);
        $settings['footer_logo']=$path;
    }
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = $_POST[$k];
            $stmt = $pdo->prepare("REPLACE INTO settings (`key`, user_id, value) VALUES (?, ?, ?)");
            $stmt->execute([$k, $k==='footer_text'||$k==='footer_logo'||$k==='footer_logo_width'||$k==='footer_logo_height'?NULL:$uid, $val]);
            $settings[$k] = $val;
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
    <?php if($_SESSION['role']==='admin'): ?>
    <div class="col-md-6 mb-3">
      <label class="form-label">Footer Yazısı</label>
      <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars($settings['footer_text']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Footer Logo</label>
      <input type="file" name="footer_logo" class="form-control">
      <?php if($settings['footer_logo']): ?>
      <img src="/<?= htmlspecialchars($settings['footer_logo']) ?>" alt="footer" style="max-width:200px;" class="mt-2">
      <?php endif; ?>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Footer Logo Genişlik</label>
      <input type="number" name="footer_logo_width" class="form-control" value="<?= htmlspecialchars($settings['footer_logo_width']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Footer Logo Yükseklik</label>
      <input type="number" name="footer_logo_height" class="form-control" value="<?= htmlspecialchars($settings['footer_logo_height']) ?>">
    </div>
    <?php endif; ?>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php if(!empty($save_message)): ?>
<div class="alert alert-success mt-3">
  <?= $save_message ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

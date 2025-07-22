<?php
require __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

$keys = ['mail_logo','smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email'];
$settings=[];
foreach($keys as $k){
    $stmt=$pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->execute([$k]);
    $settings[$k]=$stmt->fetchColumn() ?: '';
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!empty($_FILES['mail_logo']['tmp_name'])){
        $dir='uploads';
        if(!is_dir($dir)) mkdir($dir,0777,true);
        $path=$dir.'/'.basename($_FILES['mail_logo']['name']);
        move_uploaded_file($_FILES['mail_logo']['tmp_name'],$path);
        $stmt=$pdo->prepare("REPLACE INTO settings (`key`,value) VALUES ('mail_logo',?)");
        $stmt->execute([$path]);
        $settings['mail_logo']=$path;
    }
    foreach(['smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email'] as $k){
        if(isset($_POST[$k])){
            $stmt=$pdo->prepare("REPLACE INTO settings (`key`,value) VALUES (?,?)");
            $stmt->execute([$k,$_POST[$k]]);
            $settings[$k]=$_POST[$k];
        }
    }
    $save_message='Ayarlar kaydedildi';
    if(isset($_POST['send_test']) && !empty($_POST['test_email'])){
        $err='';
        $sent=sendMail($pdo,$_POST['test_email'],'Test','Bu bir test e-postasidir.',$err,null,null);
        $test_message=$sent ? 'Test maili gönderildi' : 'Gönderim başarısız: '.$err;
    }
}

include __DIR__.'/includes/header.php';
?>
<h1>E-posta Ayarları</h1>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Mail Logosu</label>
    <input type="file" name="mail_logo" class="form-control">
  </div>
  <?php if($settings['mail_logo']): ?>
  <div class="mb-3">
    <img src="/<?= htmlspecialchars($settings['mail_logo']) ?>" alt="Logo" style="max-width:200px;">
  </div>
  <?php endif; ?>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Gönderen Adı</label>
      <input type="text" name="smtp_from_name" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_name']) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Gönderen E-Posta</label>
      <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_email']) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Sunucu</label>
      <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host']) ?>">
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">Port</label>
      <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port']) ?>">
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">Şifreleme</label>
      <select name="smtp_encryption" class="form-control">
        <option value="ssl" <?= $settings['smtp_encryption']==='ssl'?'selected':'' ?>>SSL</option>
        <option value="tls" <?= $settings['smtp_encryption']==='tls'?'selected':'' ?>>TLS</option>
      </select>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Kullanıcı Adı</label>
      <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user']) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Şifre</label>
      <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass']) ?>">
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php if(!empty($save_message)): ?>
<div class="alert alert-success mt-3">
  <?= $save_message ?>
</div>
<?php endif; ?>
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
<?php include __DIR__.'/includes/footer.php'; ?>

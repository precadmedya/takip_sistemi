<?php
require __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

$keys = ['mail_logo','smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email','smtp_timeout','smtp_reminder_copy'];
$settings=[];
$errors=[];
$save_message='';
$test_message='';
$test_success=null;
foreach($keys as $k){
    $stmt=$pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->execute([$k]);
    $settings[$k]=$stmt->fetchColumn() ?: '';
}
$configDefaults = include __DIR__.'/config/config.php';
$smtpDefaults = $configDefaults['smtp'] ?? [];
if(($settings['smtp_host'] ?? '')==='') $settings['smtp_host'] = $smtpDefaults['host'] ?? '';
if(($settings['smtp_port'] ?? '')==='') $settings['smtp_port'] = $smtpDefaults['port'] ?? '';
if(($settings['smtp_encryption'] ?? '')==='') $settings['smtp_encryption'] = $smtpDefaults['encryption'] ?? '';
if(($settings['smtp_user'] ?? '')==='') $settings['smtp_user'] = $smtpDefaults['username'] ?? '';
if(($settings['smtp_pass'] ?? '')==='') $settings['smtp_pass'] = $smtpDefaults['password'] ?? '';
if(($settings['smtp_from_name'] ?? '')==='') $settings['smtp_from_name'] = $smtpDefaults['from_name'] ?? '';
if(($settings['smtp_from_email'] ?? '')==='') $settings['smtp_from_email'] = $smtpDefaults['from_email'] ?? '';
if(($settings['smtp_timeout'] ?? '')==='') $settings['smtp_timeout'] = $smtpDefaults['timeout'] ?? 20;
if(($settings['smtp_reminder_copy'] ?? '')==='') $settings['smtp_reminder_copy'] = $smtpDefaults['reminder_copy_email'] ?? '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $fieldLabels=[
        'smtp_host'=>'Sunucu',
        'smtp_port'=>'Port',
        'smtp_encryption'=>'Şifreleme',
        'smtp_user'=>'Kullanıcı Adı',
        'smtp_pass'=>'Şifre',
        'smtp_from_email'=>'Gönderen E-Posta'
    ];
    if(!empty($_FILES['mail_logo']['tmp_name'])){
        $dir='uploads';
        if(!is_dir($dir)) mkdir($dir,0777,true);
        $path=$dir.'/'.basename($_FILES['mail_logo']['name']);
        move_uploaded_file($_FILES['mail_logo']['tmp_name'],$path);
        $stmt=$pdo->prepare("REPLACE INTO settings (`key`,value) VALUES ('mail_logo',?)");
        $stmt->execute([$path]);
        $settings['mail_logo']=$path;
    }
    $required=['smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_email'];
    foreach($required as $field){
        if(isset($_POST[$field]) && trim($_POST[$field])===''){
            $label=$fieldLabels[$field] ?? $field;
            $errors[]=$label.' alanını boş bırakmayın.';
        }
    }
    if(!empty($_POST['smtp_from_email']) && !filter_var($_POST['smtp_from_email'], FILTER_VALIDATE_EMAIL)){
        $errors[]='Gönderen e-posta adresi geçerli değil.';
    }
    if(!empty($_POST['smtp_port']) && (int)$_POST['smtp_port']<=0){
        $errors[]='Port değeri pozitif bir sayı olmalıdır.';
    }
    if(!empty($_POST['smtp_reminder_copy']) && !filter_var($_POST['smtp_reminder_copy'], FILTER_VALIDATE_EMAIL)){
        $errors[]='Yönetici kopya e-posta adresi geçerli değil.';
    }
    $timeout = isset($_POST['smtp_timeout']) ? (int)$_POST['smtp_timeout'] : 20;
    if($timeout < 5){
        $errors[]='Zaman aşımı değeri 5 saniyeden az olamaz.';
    }
    if(empty($errors)){
        foreach(['smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email','smtp_timeout','smtp_reminder_copy'] as $k){
            if(isset($_POST[$k])){
                $stmt=$pdo->prepare("REPLACE INTO settings (`key`,value) VALUES (?,?)");
                if($k==='smtp_timeout'){
                    $value=$timeout;
                }elseif($k==='smtp_port'){
                    $value=(int)$_POST[$k];
                }elseif($k==='smtp_encryption'){
                    $value=in_array($_POST[$k],['ssl','tls','none'],true) ? $_POST[$k] : 'ssl';
                }elseif($k==='smtp_reminder_copy'){
                    $value=trim($_POST[$k]);
                }else{
                    $value=trim($_POST[$k]);
                }
                $stmt->execute([$k,$value]);
                $settings[$k]=$value;
            }
        }
        $save_message='Ayarlar kaydedildi';
    }
    if(isset($_POST['send_test']) && !empty($_POST['test_email'])){
        $err='';
        $sent=sendMail($pdo,$_POST['test_email'],'Test','Bu bir test e-postasıdır.',$err,null,null);
        $test_success=$sent;
        $test_message=$sent ? 'Test maili gönderildi' : 'Gönderim başarısız: '.$err;
    }
}

include __DIR__.'/includes/header.php';
?>
<h1>E-posta Ayarları</h1>
<div class="alert alert-secondary">
  <strong>Önerilen Yandex Ayarları:</strong>
  <div class="row mt-2">
    <div class="col-md-4"><span class="fw-semibold">Sunucu:</span> smtp.yandex.com</div>
    <div class="col-md-4"><span class="fw-semibold">Port:</span> 465</div>
    <div class="col-md-4"><span class="fw-semibold">Şifreleme:</span> SSL</div>
  </div>
  <div class="row">
    <div class="col-md-6"><span class="fw-semibold">Kullanıcı Adı:</span> muhasebe@precadmedya.com.tr</div>
    <div class="col-md-6"><span class="fw-semibold">Zaman Aşımı:</span> 20 saniye</div>
  </div>
</div>
<?php if(!empty($errors)): ?>
<div class="alert alert-danger">
  <ul class="mb-0">
    <?php foreach($errors as $err): ?>
    <li><?= htmlspecialchars($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Mail Logosu</label>
    <input type="file" name="mail_logo" class="form-control">
    <div class="form-text">E-postalara otomatik olarak eklenecek şirket logonuz.</div>
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
      <div class="form-text">Müşterilerin gelen kutusunda göreceği ad.</div>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Gönderen E-Posta</label>
      <input type="email" name="smtp_from_email" class="form-control" value="<?= htmlspecialchars($settings['smtp_from_email']) ?>">
      <div class="form-text">Yandex hesabınızla eşleşmelidir (muhasebe@precadmedya.com.tr).</div>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Yönetici Kopya E-Postası</label>
      <input type="email" name="smtp_reminder_copy" class="form-control" value="<?= htmlspecialchars($settings['smtp_reminder_copy']) ?>">
      <div class="form-text">Hatırlatma maillerinin kopyası bu adrese de gönderilir (örn: info@precadmedya.com.tr).</div>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Sunucu</label>
      <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host']) ?>">
      <div class="form-text">Örn: smtp.yandex.com</div>
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">Port</label>
      <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port']) ?>">
      <div class="form-text">SSL için 465, TLS için 587.</div>
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">Şifreleme</label>
      <select name="smtp_encryption" class="form-control">
        <option value="ssl" <?= $settings['smtp_encryption']==='ssl'?'selected':'' ?>>SSL</option>
        <option value="tls" <?= $settings['smtp_encryption']==='tls'?'selected':'' ?>>TLS</option>
        <option value="none" <?= $settings['smtp_encryption']==='none'?'selected':'' ?>>Şifreleme Yok</option>
      </select>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Kullanıcı Adı</label>
      <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user']) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Şifre</label>
      <div class="input-group">
        <input type="password" name="smtp_pass" id="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass']) ?>">
        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">Göster</button>
      </div>
    </div>
    <div class="col-md-2 mb-3">
      <label class="form-label">Zaman Aşımı (sn)</label>
      <input type="number" name="smtp_timeout" min="5" class="form-control" value="<?= htmlspecialchars($settings['smtp_timeout'] ?: 20) ?>">
      <div class="form-text">Sunucuya bağlantı denemesi kaç saniye sürsün?</div>
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
<?php if($test_message!==''): ?>
<div class="alert <?= $test_success ? 'alert-success' : 'alert-danger' ?>"><?= $test_message ?></div>
<?php endif; ?>
<form method="post">
  <div class="input-group mb-3">
    <input type="email" name="test_email" class="form-control" placeholder="E-posta" required>
    <button type="submit" name="send_test" value="1" class="btn btn-secondary">Test Mail Gönder</button>
  </div>
</form>
<script>
function togglePassword(){
  const input=document.getElementById('smtp_pass');
  if(!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

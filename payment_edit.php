<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM payments WHERE id=?');
$stmt->execute([$id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$payment){
    header('Location: /services.php');
    exit;
}
$usdRate = getUsdRate($pdo);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $amount_try = $currency==='USD' ? $amount*$usdRate : $amount;
    $stmt = $pdo->prepare('UPDATE payments SET amount_try=?, amount_orig=?, currency=? WHERE id=?');
    $stmt->execute([$amount_try,$amount,$currency,$id]);
    $redirect = $payment['service_id'] ? '/service.php?id='.$payment['service_id'] : '/customers.php';
    header('Location: '.$redirect);
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Tahsilat Düzenle</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Tutar</label>
    <input type="text" name="amount" class="form-control" value="<?= $payment['amount_orig'] ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Para Birimi</label>
    <select name="currency" class="form-control">
      <option value="TRY" <?= $payment['currency']=='TRY'?'selected':'' ?>>TL</option>
      <option value="USD" <?= $payment['currency']=='USD'?'selected':'' ?>>USD</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="<?= $payment['service_id'] ? '/service.php?id='.$payment['service_id'] : '/customers.php' ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM provider_payments WHERE id=?');
$stmt->execute([$id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$payment){
    header('Location: providers.php');
    exit;
}
$provider_id = $payment['provider_id'];
$usdRate = getUsdRate($pdo);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $pay_date = $_POST['pay_date'] ?: date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $amount_try = $currency==='USD' ? $amount*$usdRate : $amount;
    $upd = $pdo->prepare('UPDATE provider_payments SET amount_try=?, amount_orig=?, currency=?, pay_date=?, notes=? WHERE id=?');
    $upd->execute([$amount_try,$amount,$currency,$pay_date,$notes,$id]);
    $_SESSION['message'] = 'Ödeme güncellendi';
    header('Location: provider.php?id='.$provider_id);
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Ödeme Düzenle</h1>
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
  <div class="mb-3">
    <label class="form-label">Ödeme Tarihi</label>
    <input type="date" name="pay_date" class="form-control" value="<?= htmlspecialchars($payment['pay_date']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Not</label>
    <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($payment['notes']) ?>">
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="provider.php?id=<?= $provider_id ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

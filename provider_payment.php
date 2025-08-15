<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$provider_id = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM providers WHERE id=?');
$stmt->execute([$provider_id]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$provider){
    header('Location: providers.php');
    exit;
}
$usdRate = getUsdRate($pdo);
$totPurch = (float)$pdo->query("SELECT SUM(price_try) FROM provider_purchases WHERE provider_id={$provider_id}")->fetchColumn();
$totPay = (float)$pdo->query("SELECT SUM(amount_try) FROM provider_payments WHERE provider_id={$provider_id}")->fetchColumn();
$balance = $totPurch - $totPay;
if($balance < 0) $balance = 0;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $pay_date = $_POST['pay_date'] ?: date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    $amount_try = $currency==='USD' ? $amount * $usdRate : $amount;
    $stmt = $pdo->prepare('INSERT INTO provider_payments(provider_id,amount_try,amount_orig,currency,pay_date,notes) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$provider_id,$amount_try,$amount,$currency,$pay_date,$notes]);
    $_SESSION['message'] = 'Ödeme kaydedildi';
    header('Location: provider.php?id='.$provider_id);
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Tedarikçi Ödeme - <?= htmlspecialchars($provider['name']) ?></h1>
<p><strong>Kalan Borç:</strong> <?= number_format($balance,2,',','.') ?> TL</p>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Tutar</label>
    <input type="text" name="amount" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Para Birimi</label>
    <select name="currency" class="form-control">
      <option value="TRY">TL</option>
      <option value="USD">USD</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Ödeme Tarihi</label>
    <input type="date" name="pay_date" class="form-control" value="<?= date('Y-m-d') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Not</label>
    <input type="text" name="notes" class="form-control">
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="provider.php?id=<?= $provider_id ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

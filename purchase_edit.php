<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT pp.*, pr.name AS provider_name FROM provider_purchases pp JOIN providers pr ON pp.provider_id=pr.id WHERE pp.id=?');
$stmt->execute([$id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$purchase){
    header('Location: providers.php');
    exit;
}
$provider_id = $purchase['provider_id'];
$usdRate = getUsdRate($pdo);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name = $_POST['item_name'];
    $qty = (int)$_POST['quantity'];
    $unit = $_POST['unit'];
    $price = (float)$_POST['unit_price'];
    $vat = (float)$_POST['vat_rate'];
    $cur = $_POST['currency'];
    $pdate = $_POST['purchase_date'];
    $paydate = $_POST['payment_date'];
    $notes = $_POST['notes'];
    $line = $qty*$price;
    $lineVat = $line*$vat/100;
    $try = $cur==='USD' ? ($line+$lineVat)*$usdRate : ($line+$lineVat);
    $stmt = $pdo->prepare('UPDATE provider_purchases SET item_name=?,quantity=?,unit=?,unit_price=?,vat_rate=?,currency=?,purchase_date=?,payment_date=?,price_try=?,notes=? WHERE id=?');
    $stmt->execute([$name,$qty,$unit,$price,$vat,$cur,$pdate,$paydate,$try,$notes,$id]);
    $_SESSION['message']='Satın alım güncellendi';
    header('Location: provider.php?id='.$provider_id);
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Satın Alım Düzenle - <?= htmlspecialchars($purchase['provider_name']) ?></h1>
<form method="post">
  <div class="mb-3"><label class="form-label">Ürün</label><input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($purchase['item_name']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Miktar</label><input type="number" name="quantity" value="<?= $purchase['quantity'] ?>" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Birim</label>
    <select name="unit" class="form-control">
      <option value="ADET" <?= $purchase['unit']=='ADET'?'selected':'' ?>>ADET</option>
      <option value="AY" <?= $purchase['unit']=='AY'?'selected':'' ?>>AY</option>
      <option value="YIL" <?= $purchase['unit']=='YIL'?'selected':'' ?>>YIL</option>
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Birim Fiyat</label><input type="text" name="unit_price" value="<?= $purchase['unit_price'] ?>" class="form-control"></div>
  <div class="mb-3"><label class="form-label">KDV</label>
    <select name="vat_rate" class="form-control">
      <option value="0" <?= $purchase['vat_rate']==0?'selected':'' ?>>%0</option>
      <option value="10" <?= $purchase['vat_rate']==10?'selected':'' ?>>%10</option>
      <option value="20" <?= $purchase['vat_rate']==20?'selected':'' ?>>%20</option>
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Döviz</label>
    <select name="currency" class="form-control">
      <option value="TRY" <?= $purchase['currency']=='TRY'?'selected':'' ?>>TRY</option>
      <option value="USD" <?= $purchase['currency']=='USD'?'selected':'' ?>>USD</option>
    </select>
  </div>
  <div class="mb-3"><label class="form-label">Satın Alma Tarihi</label><input type="date" name="purchase_date" value="<?= $purchase['purchase_date'] ?>" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Ödeme Tarihi</label><input type="date" name="payment_date" value="<?= $purchase['payment_date'] ?>" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Not</label><input type="text" name="notes" value="<?= htmlspecialchars($purchase['notes']) ?>" class="form-control"></div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="provider.php?id=<?= $provider_id ?>" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

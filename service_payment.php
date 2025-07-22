<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$stmt = $pdo->prepare('SELECT s.*, c.full_name FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.id=?');
$stmt->execute([$service_id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$service){
    header('Location: /services.php');
    exit;
}
$customer_id = $service['customer_id'];

$usdRate = getUsdRate($pdo);

// mevcut borcu hesapla
$paidStmt = $pdo->prepare('SELECT SUM(amount_try) FROM payments WHERE service_id=?');
$paidStmt->execute([$service_id]);
$paid_try = (float)$paidStmt->fetchColumn();
$service_total_try = $service['price_try'] * (1 + $service['vat_rate']/100);
$remain_try = $service_total_try - $paid_try;
if ($remain_try < 0) $remain_try = 0;
$remain_cur = $service['currency'] === 'USD' ? ($remain_try / ($usdRate ?: 1)) : $remain_try;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $amount_try = $currency==='USD' ? $amount * $usdRate : $amount;
    $stmt = $pdo->prepare('INSERT INTO payments (customer_id, service_id, amount_try, amount_orig, currency) VALUES (?,?,?,?,?)');
    $stmt->execute([$customer_id, $service_id, $amount_try, $amount, $currency]);

    if(isset($_POST['renew'])){
        $years = (int)$_POST['years'];
        $renew_price = (float)$_POST['renew_price'];
        $renew_currency = $_POST['renew_currency'];
        $renew_vat_rate = (float)$_POST['renew_vat_rate'];
        $renew_apply_vat = isset($_POST['renew_apply_vat']);
        $price = $renew_price * $years;
        if($renew_apply_vat){
            $price *= (1 + $renew_vat_rate/100);
        }
        $price_try = $renew_currency==='USD' ? $price * $usdRate : $price;
        $start = $service['due_date'];
        $due = date('Y-m-d', strtotime($start.' +'.$years.' year'));
        $duration = (int)((strtotime($due)-strtotime($start))/86400);
        $stmt = $pdo->prepare("INSERT INTO services (customer_id, product_id, provider_id, site_name, service_type, start_date, due_date, duration, unit, price, currency, vat_rate, price_try, status, notes, created_at) VALUES (?,?,?,?,?,?,?,?, 'gün', ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$customer_id,$service['product_id'],$service['provider_id'],$service['site_name'],$service['service_type'],$start,$due,$duration,$renew_price*$years,$renew_currency,$renew_vat_rate,$price_try,$service['status'],$service['notes']]);
    }

    header('Location: /customer.php?id='.$customer_id);
    exit;
}

$total_default = $service['price'];
$default_years = 1;
$default_apply_vat = true;
$calc_total = function($yrs,$vat) use($service){
    $price = $service['price'] * $yrs;
    if($vat){
        $price *= (1 + $service['vat_rate']/100);
    }
    return $price;
};

include __DIR__.'/includes/header.php';
?>
<h1>Hizmet Tahsilatı - <?= htmlspecialchars($service['full_name']) ?></h1>
<p>Hizmet: <?= htmlspecialchars($service['service_type']) ?> - <?= htmlspecialchars($service['site_name']) ?></p>
<p><strong>Toplam Bor\u00e7:</strong> <?= number_format($remain_cur,2,',','.') ?> <?= $service['currency'] ?> (<?= number_format($remain_try,2,',','.') ?> TL)</p>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Tutar</label>
    <input type="text" name="amount" class="form-control" value="<?= number_format($remain_cur,2,'.','') ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Para Birimi</label>
    <select name="currency" class="form-control">
      <option value="TRY" <?= $service['currency']=='TRY'?'selected':'' ?>>TL</option>
      <option value="USD" <?= $service['currency']=='USD'?'selected':'' ?>>USD</option>
    </select>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="renew" name="renew">
    <label class="form-check-label" for="renew">Tahsilattan sonra hizmeti uzat</label>
  </div>
  <div id="renewFields" style="display:none;">
    <div class="mb-3">
      <label class="form-label">Ka\u00e7 Y\u0131l Uzat\u0131ls\u0131n</label>
      <select name="years" class="form-control">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Fiyat</label>
      <input type="text" name="renew_price" id="renew_price" value="<?= $service['price'] ?>" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label">Para Birimi</label>
      <select name="renew_currency" id="renew_currency" class="form-control">
        <option value="TRY" <?= $service['currency']=='TRY'?'selected':'' ?>>TL</option>
        <option value="USD" <?= $service['currency']=='USD'?'selected':'' ?>>USD</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">KDV Oran\u0131</label>
      <select name="renew_vat_rate" id="renew_vat_rate" class="form-control">
        <option value="0" <?= $service['vat_rate']==0?'selected':'' ?>>Yok</option>
        <option value="10" <?= $service['vat_rate']==10?'selected':'' ?>>%10</option>
        <option value="20" <?= $service['vat_rate']==20?'selected':'' ?>>%20</option>
      </select>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="renew_apply_vat" id="renew_vat" <?= $service['vat_rate']>0?'checked':'' ?>>
      <label class="form-check-label" for="renew_vat">KDV Eklensin</label>
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="/customer.php?id=<?= $customer_id ?>" class="btn btn-secondary">\u0130ptal</a>
</form>
<script>
 document.getElementById('renew').addEventListener('change',function(){
   document.getElementById('renewFields').style.display=this.checked?'block':'none';
 });
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';

$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$stmt = $pdo->prepare('SELECT s.*, c.full_name FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.id=?');
$stmt->execute([$service_id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$service){
    header('Location: /services.php');
    exit;
}
$customer_id = $service['customer_id'];

$rateStmt = $pdo->query("SELECT usd_try FROM exchange_rates ORDER BY rate_date DESC LIMIT 1");
$usdRate = (float)$rateStmt->fetchColumn();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $years = (int)$_POST['years'];
    $apply_vat = isset($_POST['apply_vat']);
    $amount_try = $currency==='USD' ? $amount * $usdRate : $amount;
    $stmt = $pdo->prepare('INSERT INTO payments (customer_id, service_id, amount_try, amount_orig, currency) VALUES (?,?,?,?,?)');
    $stmt->execute([$customer_id, $service_id, $amount_try, $amount, $currency]);

    // create renewal service
    $price = $service['price'] * $years;
    if($apply_vat){
        $price *= (1 + $service['vat_rate']/100);
    }
    $price_try = $service['currency']==='USD' ? $price * $usdRate : $price;
    $start = date('Y-m-d', strtotime($service['start_date'].' + '.$service['duration'].' '.$service['unit']));
    $stmt = $pdo->prepare("INSERT INTO services (customer_id, product_id, provider_id, site_name, service_type, start_date, duration, unit, price, currency, vat_rate, price_try, status, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
    $stmt->execute([$customer_id,$service['product_id'],$service['provider_id'],$service['site_name'],$service['service_type'],$start,$years,$service['unit'],$service['price']*$years,$service['currency'],$service['vat_rate'],$price_try,$service['status'],$service['notes']]);

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
<form method="post">
  <div class="mb-3">
    <label class="form-label">Yıl Uzat</label>
    <select name="years" id="years" class="form-control">
      <option value="1">1</option>
      <option value="2">2</option>
      <option value="3">3</option>
    </select>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="apply_vat" id="vat" checked>
    <label class="form-check-label" for="vat">KDV Eklensin</label>
  </div>
  <div class="mb-3">
    <label class="form-label">Tutar</label>
    <input type="text" name="amount" id="amount" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Para Birimi</label>
    <select name="currency" class="form-control">
      <option value="TRY">TL</option>
      <option value="USD">USD</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="/customer.php?id=<?= $customer_id ?>" class="btn btn-secondary">İptal</a>
</form>
<script>
function calc(){
  var years = parseInt(document.getElementById('years').value);
  var vat = document.getElementById('vat').checked ? 1 : 0;
  var price = <?= json_encode($service['price']) ?> * years;
  if(vat) price += price * <?= json_encode($service['vat_rate']) ?>/100;
  document.getElementById('amount').value = price.toFixed(2);
}
calc();
document.getElementById('years').addEventListener('change',calc);
document.getElementById('vat').addEventListener('change',calc);
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

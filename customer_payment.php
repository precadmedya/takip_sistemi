<?php
require __DIR__.'/includes/auth.php';

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$customer){
    header('Location: customers.php');
    exit;
}

$rateStmt = $pdo->query("SELECT usd_try FROM exchange_rates ORDER BY rate_date DESC LIMIT 1");
$usdRate = (float)$rateStmt->fetchColumn();
$services = $pdo->prepare('SELECT id, service_type, site_name FROM services WHERE customer_id=? ORDER BY id DESC');
$services->execute([$customer_id]);
$services = $services->fetchAll(PDO::FETCH_ASSOC);
$balStmt = $pdo->prepare("SELECT IFNULL(SUM(s.price_try*(1+s.vat_rate/100)),0) - IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.customer_id=c.id),0) FROM customers c LEFT JOIN services s ON s.customer_id=c.id WHERE c.id=? GROUP BY c.id");
$balStmt->execute([$customer_id]);
$balance = (float)$balStmt->fetchColumn();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $serviceId = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $amount_try = $currency === 'USD' ? $amount * $usdRate : $amount;
    $stmt = $pdo->prepare('INSERT INTO payments (customer_id, service_id, amount_try, amount_orig, currency) VALUES (?,?,?,?,?)');
    $stmt->execute([$customer_id, $serviceId, $amount_try, $amount, $currency]);
    header('Location: customers.php');
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Tahsilat Yap - <?= htmlspecialchars($customer['full_name']) ?></h1>
<p><strong>Toplam Borç:</strong> <?= number_format($balance,2,',','.') ?> ₺</p>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Hizmet (opsiyonel)</label>
    <select name="service_id" class="form-control">
      <option value="">Genel</option>
      <?php foreach($services as $s): ?>
      <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['service_type'].' - '.$s['site_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
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
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="customers.php" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

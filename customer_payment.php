<?php
require __DIR__.'/includes/auth.php';

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$customer){
    header('Location: /customers.php');
    exit;
}

$rateStmt = $pdo->query("SELECT usd_try FROM exchange_rates ORDER BY rate_date DESC LIMIT 1");
$usdRate = (float)$rateStmt->fetchColumn();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $amount_try = $currency === 'USD' ? $amount * $usdRate : $amount;
    $stmt = $pdo->prepare('INSERT INTO payments (customer_id, amount_try, amount_orig, currency) VALUES (?,?,?,?)');
    $stmt->execute([$customer_id, $amount_try, $amount, $currency]);
    header('Location: /customers.php');
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Tahsilat Yap - <?= htmlspecialchars($customer['full_name']) ?></h1>
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
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="/customers.php" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

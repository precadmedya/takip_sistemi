<?php
require __DIR__.'/includes/auth.php';
$customers = $pdo->query("SELECT id, full_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, name, price, currency, vat_rate FROM products")->fetchAll(PDO::FETCH_ASSOC);
$providers = $pdo->query("SELECT id, name FROM providers")->fetchAll(PDO::FETCH_ASSOC);
$rateStmt = $pdo->query("SELECT usd_try FROM exchange_rates ORDER BY rate_date DESC LIMIT 1");
$usdRate = (float)$rateStmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['price'];
    $price_try = $_POST['currency'] === 'USD' ? $price * $usdRate : $price;
    $stmt = $pdo->prepare("INSERT INTO services (customer_id, product_id, provider_id, site_name, service_type, start_date, duration, unit, price, currency, vat_rate, price_try, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['customer_id'],
        $_POST['product_id'],
        $_POST['provider_id'],
        $_POST['site_name'],
        $_POST['service_type'],
        $_POST['start_date'],
        $_POST['duration'],
        $_POST['unit'],
        $price,
        $_POST['currency'],
        $_POST['vat_rate'],
        $price_try,
        $_POST['status'],
        $_POST['notes']
    ]);
    header('Location: /services.php');
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Hizmet Ekle</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Müşteri</label>
    <select name="customer_id" class="form-control" required>
      <?php foreach ($customers as $c): ?>
      <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Ürün</label>
    <select name="product_id" id="product" class="form-control" required>
      <option value="">Seçiniz</option>
      <?php foreach ($products as $p): ?>
      <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-currency="<?= $p['currency'] ?>" data-vat="<?= $p['vat_rate'] ?>"><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Sağlayıcı</label>
    <select name="provider_id" class="form-control">
      <option value="">Seçiniz</option>
      <?php foreach ($providers as $p): ?>
      <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Site / Alan Adı</label>
    <input type="text" name="site_name" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Hizmet Tipi</label>
    <input type="text" name="service_type" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Başlangıç Tarihi</label>
    <input type="date" name="start_date" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Süre</label>
    <input type="number" name="duration" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Birim</label>
    <select name="unit" class="form-control">
      <option value="yıl">Yıl</option>
      <option value="ay">Ay</option>
      <option value="adet">Adet</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Fiyat</label>
    <input type="text" name="price" id="price" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Döviz</label>
    <select name="currency" id="currency" class="form-control">
      <option value="TRY">TRY</option>
      <option value="USD">USD</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">KDV Oranı</label>
    <select name="vat_rate" id="vat" class="form-control">
      <option value="0">Yok</option>
      <option value="10">%10</option>
      <option value="20">%20</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Durum</label>
    <select name="status" class="form-control">
      <option value="aktif">Aktif</option>
      <option value="pasif">Pasif</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Not</label>
    <textarea name="notes" class="form-control"></textarea>
  </div>
  <div class="mb-3">
    <strong>Toplam: <span id="total">0</span></strong>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<script>
function updateFromProduct(){
  var sel=document.getElementById('product');
  var opt=sel.options[sel.selectedIndex];
  if(opt && opt.dataset.price){
    document.getElementById('price').value=opt.dataset.price;
    document.getElementById('currency').value=opt.dataset.currency;
    document.getElementById('vat').value=opt.dataset.vat;
    updateTotal();
  }
}
function updateTotal(){
  var price=parseFloat(document.getElementById('price').value)||0;
  var vat=parseFloat(document.getElementById('vat').value)||0;
  var total=price+price*vat/100;
  document.getElementById('total').innerText=total.toFixed(2);
}
document.getElementById('product').addEventListener('change',updateFromProduct);
document.getElementById('price').addEventListener('input',updateTotal);
document.getElementById('vat').addEventListener('change',updateTotal);
updateTotal();
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

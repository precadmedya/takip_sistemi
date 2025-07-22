<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
$customers = $pdo->query("SELECT id, full_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, name, price, currency, vat_rate FROM products")->fetchAll(PDO::FETCH_ASSOC);
$providers = $pdo->query("SELECT id, name FROM providers")->fetchAll(PDO::FETCH_ASSOC);
$usdRate = getUsdRate($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = (float)$_POST['price'];
    $currency = $_POST['currency'];
    $vat_rate = (float)$_POST['vat_rate'];
    $start = $_POST['start_date'];
    $due = $_POST['due_date'] ?: date('Y-m-d', strtotime($start.' +1 year'));
    $price_try = $currency === 'USD' ? $price * $usdRate : $price;
    $duration = (int)((strtotime($due) - strtotime($start)) / 86400);
    $stmt = $pdo->prepare("INSERT INTO services (customer_id, product_id, provider_id, site_name, service_type, start_date, due_date, duration, unit, price, currency, vat_rate, price_try, status, notes, created_at) VALUES (?, ?, ?, ?, '', ?, ?, ?, 'gün', ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['customer_id'],
        $_POST['product_id'],
        $_POST['provider_id'],
        $_POST['site_name'],
        $start,
        $due,
        $duration,
        $price,
        $currency,
        $vat_rate,
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
    <label class="form-label">Başlangıç Tarihi</label>
    <input type="date" name="start_date" id="start" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Ödeme Tarihi</label>
    <input type="date" name="due_date" id="due" class="form-control">
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
    <strong>Toplam Tutar (TL): <span id="total">0</span></strong><br>
    <strong>KDV Tutarı (TL): <span id="vat_t">0</span></strong><br>
    <strong>Güncel Kur: <span id="rate"><?= number_format($usdRate,2,',','.') ?></span></strong><br>
    <strong>Genel Toplam (TL): <span id="grand">0</span></strong>
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
  var rate = <?= $usdRate ? $usdRate : 0 ?>;
  var price = parseFloat(document.getElementById('price').value)||0;
  var vat = parseFloat(document.getElementById('vat').value)||0;
  var cur = document.getElementById('currency').value;
  var tl = cur==='USD' ? price*rate : price;
  var vatTl = tl*vat/100;
  document.getElementById('total').innerText=tl.toFixed(2);
  document.getElementById('vat_t').innerText=vatTl.toFixed(2);
  document.getElementById('grand').innerText=(tl+vatTl).toFixed(2);
}
document.getElementById('product').addEventListener('change',updateFromProduct);
document.getElementById('price').addEventListener('input',updateTotal);
document.getElementById('vat').addEventListener('change',updateTotal);
document.getElementById('currency').addEventListener('change',updateTotal);
document.getElementById('start').addEventListener('change',function(){
  if(!document.getElementById('due').value){
    var start=new Date(this.value);
    if(start.toString()!=='Invalid Date'){
      start.setFullYear(start.getFullYear()+1);
      document.getElementById('due').value=start.toISOString().slice(0,10);
    }
  }
});
updateTotal();
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

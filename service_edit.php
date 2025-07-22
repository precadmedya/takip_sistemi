<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM services WHERE id=?');
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$service){
    header('Location: /services.php');
    exit;
}

$customers = $pdo->query("SELECT id, full_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, name, price, currency, vat_rate FROM products")->fetchAll(PDO::FETCH_ASSOC);
$providers = $pdo->query("SELECT id, name FROM providers")->fetchAll(PDO::FETCH_ASSOC);
$usdRate = getUsdRate($pdo);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $price = (float)$_POST['price'];
    $currency = $_POST['currency'];
    $vat_rate = (float)$_POST['vat_rate'];
    $start = $_POST['start_date'];
    $due = $_POST['due_date'] ?: date('Y-m-d', strtotime($start.' +1 year'));
    $price_try = $currency==='USD' ? $price*$usdRate : $price;
    $duration = (int)((strtotime($due)-strtotime($start))/86400);
    $stmt = $pdo->prepare('UPDATE services SET customer_id=?, product_id=?, provider_id=?, site_name=?, start_date=?, due_date=?, duration=?, price=?, currency=?, vat_rate=?, price_try=?, status=?, notes=? WHERE id=?');
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
        $_POST['notes'],
        $id
    ]);
    header('Location: /service.php?id='.$id);
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Hizmet Düzenle</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Müşteri</label>
    <select name="customer_id" class="form-control" required>
      <?php foreach($customers as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $c['id']==$service['customer_id']?'selected':'' ?>><?= htmlspecialchars($c['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Ürün</label>
    <select name="product_id" id="product" class="form-control" required>
      <option value="">Seçiniz</option>
      <?php foreach($products as $p): ?>
      <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-currency="<?= $p['currency'] ?>" data-vat="<?= $p['vat_rate'] ?>" <?= $p['id']==$service['product_id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Sağlayıcı</label>
    <select name="provider_id" class="form-control">
      <option value="">Seçiniz</option>
      <?php foreach($providers as $p): ?>
      <option value="<?= $p['id'] ?>" <?= $p['id']==$service['provider_id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Site / Alan Adı</label>
    <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($service['site_name']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Başlangıç Tarihi</label>
    <input type="date" name="start_date" id="start" class="form-control" value="<?= $service['start_date'] ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Ödeme Tarihi</label>
    <input type="date" name="due_date" id="due" class="form-control" value="<?= $service['due_date'] ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Fiyat</label>
    <input type="text" name="price" id="price" class="form-control" value="<?= $service['price'] ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Döviz</label>
    <select name="currency" id="currency" class="form-control">
      <option value="TRY" <?= $service['currency']=='TRY'?'selected':'' ?>>TRY</option>
      <option value="USD" <?= $service['currency']=='USD'?'selected':'' ?>>USD</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">KDV Oranı</label>
    <select name="vat_rate" id="vat" class="form-control">
      <option value="0" <?= $service['vat_rate']==0?'selected':'' ?>>Yok</option>
      <option value="10" <?= $service['vat_rate']==10?'selected':'' ?>>%10</option>
      <option value="20" <?= $service['vat_rate']==20?'selected':'' ?>>%20</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Durum</label>
    <select name="status" class="form-control">
      <option value="aktif" <?= $service['status']=='aktif'?'selected':'' ?>>Aktif</option>
      <option value="pasif" <?= $service['status']=='pasif'?'selected':'' ?>>Pasif</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Not</label>
    <textarea name="notes" class="form-control"><?= htmlspecialchars($service['notes']) ?></textarea>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="/services.php" class="btn btn-secondary">İptal</a>
</form>
<script>
function updateFromProduct(){
  var sel=document.getElementById('product');
  var opt=sel.options[sel.selectedIndex];
  if(opt && opt.dataset.price){
    document.getElementById('price').value=opt.dataset.price;
    document.getElementById('currency').value=opt.dataset.currency;
    document.getElementById('vat').value=opt.dataset.vat;
  }
}

document.getElementById('product').addEventListener('change',updateFromProduct);
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

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
    $serviceId = $pdo->lastInsertId();
    if(!empty($_POST['item_name'])){
        $itemStmt = $pdo->prepare('INSERT INTO service_items (service_id,item_name,quantity,unit,unit_price,vat_rate) VALUES (?,?,?,?,?,?)');
        foreach($_POST['item_name'] as $i => $name){
            if(trim($name)==='') continue;
            $itemStmt->execute([
                $serviceId,
                $name,
                (int)($_POST['quantity'][$i] ?? 1),
                $_POST['unit'][$i] ?? '',
                (float)($_POST['unit_price'][$i] ?? 0),
                (float)($_POST['item_vat'][$i] ?? 0)
            ]);
        }
    }
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

  <h3>Hizmet / Ürün Detayı</h3>
  <table class="table" id="items">
    <thead>
      <tr>
        <th>Hizmet / Ürün</th>
        <th>Miktar</th>
        <th>Birim</th>
        <th>Birim Fiyat</th>
        <th>KDV</th>
        <th>Toplam</th>
        <th></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <button type="button" class="btn btn-secondary mb-3" id="addRow">Satır Ekle</button>

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
function addRow(){
  var tbody=document.querySelector('#items tbody');
  var tr=document.createElement('tr');
  tr.innerHTML='<td><input type="text" name="item_name[]" class="form-control"></td>'+
    '<td><input type="number" name="quantity[]" value="1" class="form-control qty"></td>'+
    '<td><select name="unit[]" class="form-control unit">'+
      '<option value="adet">Adet</option><option value="ay">Ay</option><option value="yıl">Yıl</option>'+
    '</select></td>'+
    '<td><input type="text" name="unit_price[]" class="form-control price"></td>'+
    '<td><select name="item_vat[]" class="form-control vat">'+
       '<option value="0">%0</option><option value="1">%1</option><option value="10">%10</option><option value="20">%20</option>'+
    '</select></td>'+
    '<td class="row-total">0</td>'+
    '<td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>';
  tbody.appendChild(tr);
  tr.querySelector('.remove-row').addEventListener('click',function(){tr.remove();updateTotal();});
  ['input','change'].forEach(function(ev){
     tr.querySelector('.qty').addEventListener(ev,updateTotal);
     tr.querySelector('.price').addEventListener(ev,updateTotal);
     tr.querySelector('.vat').addEventListener(ev,updateTotal);
  });
  updateTotal();
}
function updateTotal(){
  var rate = <?= $usdRate ? $usdRate : 0 ?>;
  var cur = document.getElementById('currency').value;
  var price = parseFloat(document.getElementById('price').value)||0;
  var vat = parseFloat(document.getElementById('vat').value)||0;
  var subCur = price;
  var vatCur = price*vat/100;
  document.querySelectorAll('#items tbody tr').forEach(function(tr){
    var q = parseFloat(tr.querySelector('.qty').value)||0;
    var p = parseFloat(tr.querySelector('.price').value)||0;
    var v = parseFloat(tr.querySelector('.vat').value)||0;
    var lineSub = q*p;
    var lineVat = lineSub*v/100;
    tr.querySelector('.row-total').innerText=(lineSub+lineVat).toFixed(2);
    subCur += lineSub;
    vatCur += lineVat;
  });
  var totalTl = cur==='USD' ? subCur*rate : subCur;
  var vatTl = cur==='USD' ? vatCur*rate : vatCur;
  document.getElementById('total').innerText=totalTl.toFixed(2);
  document.getElementById('vat_t').innerText=vatTl.toFixed(2);
  document.getElementById('grand').innerText=(totalTl+vatTl).toFixed(2);
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
document.getElementById('addRow').addEventListener('click',addRow);
addRow();
updateTotal();
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

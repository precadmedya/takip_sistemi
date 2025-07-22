<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM services WHERE id=?');
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$service){
    header('Location: services.php');
    exit;
}

$customers = $pdo->query("SELECT id, full_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, name, price, currency, vat_rate FROM products")->fetchAll(PDO::FETCH_ASSOC);
$providers = $pdo->query("SELECT id, name FROM providers")->fetchAll(PDO::FETCH_ASSOC);
$usdRate = getUsdRate($pdo);

$itemStmt = $pdo->prepare('SELECT * FROM service_items WHERE service_id=?');
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $start = $_POST['start_date'];
    $due = $_POST['due_date'] ?: date('Y-m-d', strtotime($start.' +1 year'));
    $duration = (int)((strtotime($due) - strtotime($start))/86400);

    $totalTry = 0;
    if(!empty($_POST['item_name'])){
        foreach($_POST['item_name'] as $i => $name){
            $qty = (float)($_POST['quantity'][$i] ?? 1);
            $price = (float)($_POST['unit_price'][$i] ?? 0);
            $vat = (float)($_POST['item_vat'][$i] ?? 0);
            $cur = $_POST['item_currency'][$i] ?? 'TRY';
            $line = $qty * $price;
            $lineVat = $line * $vat / 100;
            $lineTl = $cur==='USD' ? ($line + $lineVat) * $usdRate : ($line + $lineVat);
            $totalTry += $lineTl;
        }
    }

    $stmt = $pdo->prepare('UPDATE services SET customer_id=?, site_name=?, start_date=?, due_date=?, duration=?, price=?, currency=?, vat_rate=?, price_try=?, status=?, notes=? WHERE id=?');
    $stmt->execute([
        $_POST['customer_id'],
        $_POST['site_name'],
        $start,
        $due,
        $duration,
        $totalTry,
        'TRY',
        0,
        $totalTry,
        $_POST['status'],
        $_POST['notes'],
        $id
    ]);

    $pdo->prepare('DELETE FROM service_items WHERE service_id=?')->execute([$id]);
    if(!empty($_POST['item_name'])){
        $itemStmt = $pdo->prepare('INSERT INTO service_items (service_id,item_name,quantity,unit,unit_price,vat_rate,currency,provider_id,description) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach($_POST['item_name'] as $i => $n){
            $name = $n==='__new__' ? ($_POST['item_custom'][$i] ?? '') : $n;
            if(trim($name)==='') continue;
            $itemStmt->execute([
                $id,
                $name,
                (int)($_POST['quantity'][$i] ?? 1),
                $_POST['unit'][$i] ?? '',
                (float)($_POST['unit_price'][$i] ?? 0),
                (float)($_POST['item_vat'][$i] ?? 0),
                $_POST['item_currency'][$i] ?? 'TRY',
                ($_POST['provider_item'][$i] ?? '') ?: null,
                $_POST['description'][$i] ?? ''
            ]);
        }
    }
    if(!empty($_POST['new_products_json'])){
        $new = json_decode($_POST['new_products_json'], true) ?: [];
        $prodStmt = $pdo->prepare('INSERT INTO products(name,unit,vat_rate,price,currency) VALUES (?,?,?,?,?)');
        foreach($new as $p){
            $prodStmt->execute([$p['name'],$p['unit'],$p['vat_rate'],$p['price'],$p['currency']]);
        }
    }
    header('Location: service.php?id='.$id);
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
  <h3>Hizmet / Ürün Detayı</h3>
  <table class="table" id="items">
    <thead>
      <tr>
        <th>Hizmet / Ürün</th>
        <th>Miktar</th>
        <th>Birim</th>
        <th>Birim Fiyat</th>
        <th>Döviz</th>
        <th>Sağlayıcı</th>
        <th>KDV</th>
        <th>Açıklama</th>
        <th>Toplam</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($items as $it): ?>
      <?php $known = false; foreach($products as $p){ if($p['name']==$it['item_name']){$known=true;break;} } ?>
      <tr>
        <td>
          <select name="item_name[]" class="form-control prod">
            <option value="">Seçiniz</option>
            <?php foreach($products as $p): ?>
            <option value="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $p['price'] ?>" data-currency="<?= $p['currency'] ?>" data-vat="<?= $p['vat_rate'] ?>" <?= $p['name']==$it['item_name']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
            <option value="__new__" <?= !$known?'selected':'' ?>>+ Özel Ürün</option>
          </select>
          <input type="text" name="item_custom[]" class="form-control mt-2 custom <?= $known?'d-none':'' ?>" placeholder="Ürün adı" value="<?= $known?'':htmlspecialchars($it['item_name']) ?>">
        </td>
        <td><input type="number" name="quantity[]" value="<?= $it['quantity'] ?>" class="form-control qty"></td>
        <td><select name="unit[]" class="form-control unit">
              <option value="adet" <?= $it['unit']=='adet'?'selected':'' ?>>Adet</option>
              <option value="ay" <?= $it['unit']=='ay'?'selected':'' ?>>Ay</option>
              <option value="yıl" <?= $it['unit']=='yıl'?'selected':'' ?>>Yıl</option>
            </select></td>
        <td><input type="text" name="unit_price[]" value="<?= $it['unit_price'] ?>" class="form-control price"></td>
        <td><select name="item_currency[]" class="form-control row-currency">
              <option value="TRY" <?= $it['currency']=='TRY'?'selected':'' ?>>TRY</option>
              <option value="USD" <?= $it['currency']=='USD'?'selected':'' ?>>USD</option>
            </select></td>
        <td><select name="provider_item[]" class="form-control provider">
              <option value="">Seçiniz</option>
              <?php foreach($providers as $pr): ?>
              <option value="<?= $pr['id'] ?>" <?= $it['provider_id']==$pr['id']?'selected':'' ?>><?= htmlspecialchars($pr['name']) ?></option>
              <?php endforeach; ?>
            </select></td>
        <td><select name="item_vat[]" class="form-control vat">
              <option value="0" <?= $it['vat_rate']==0?'selected':'' ?>>%0</option>
              <option value="1" <?= $it['vat_rate']==1?'selected':'' ?>>%1</option>
              <option value="10" <?= $it['vat_rate']==10?'selected':'' ?>>%10</option>
              <option value="20" <?= $it['vat_rate']==20?'selected':'' ?>>%20</option>
            </select></td>
        <td><input type="text" name="description[]" value="<?= htmlspecialchars($it['description']) ?>" class="form-control desc"></td>
        <td class="row-total">0</td>
        <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <button type="button" class="btn btn-secondary mb-3" id="addRow">Satır Ekle</button>
  <div class="mb-3 text-end">
    <strong>Toplam Tutar (TL): <span id="total">0</span></strong><br>
    <strong>KDV Tutarı (TL): <span id="vat_t">0</span></strong><br>
    <strong>Güncel Kur: <span id="rate"><?= number_format($usdRate,2,',','.') ?></span></strong><br>
    <strong>Genel Toplam (TL): <span id="grand">0</span></strong>
  </div>
  <input type="hidden" name="new_products_json" id="new_products_json">
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="service.php?id=<?= $service['id'] ?>" class="btn btn-secondary">İptal</a>
</form>
<script>
var productOptions = '<?php foreach($products as $p){echo "<option value=\"".$p['name']."\" data-price=\"{$p['price']}\" data-currency=\"{$p['currency']}\" data-vat=\"{$p['vat_rate']}\">".htmlspecialchars($p['name'])."</option>";} ?>' + '<option value="__new__">+ Özel Ürün</option>';
var providerOptions = '<?php foreach($providers as $p){echo "<option value=\"{$p['id']}\">".htmlspecialchars($p['name'])."</option>";} ?>';
var newProducts = [];
function initRow(tr){
  tr.querySelector('.remove-row').addEventListener('click',function(){tr.remove();updateTotal();});
  ['input','change'].forEach(function(ev){
     tr.querySelector('.qty').addEventListener(ev,updateTotal);
     tr.querySelector('.price').addEventListener(ev,updateTotal);
     tr.querySelector('.vat').addEventListener(ev,updateTotal);
     tr.querySelector('.row-currency').addEventListener(ev,updateTotal);
     tr.querySelector('.prod').addEventListener(ev,prodChanged);
  });
}
function addRow(){
  var tbody=document.querySelector('#items tbody');
  var tr=document.createElement('tr');
  tr.innerHTML='<td><select name="item_name[]" class="form-control prod"><option value="">Seçiniz</option>'+productOptions+'</select><input type="text" name="item_custom[]" class="form-control mt-2 d-none custom" placeholder="Ürün adı"></td>'+
    '<td><input type="number" name="quantity[]" value="1" class="form-control qty"></td>'+
    '<td><select name="unit[]" class="form-control unit">'+
      '<option value="adet">Adet</option><option value="ay">Ay</option><option value="yıl">Yıl</option>'+
    '</select></td>'+
    '<td><input type="text" name="unit_price[]" class="form-control price"></td>'+
    '<td><select name="item_currency[]" class="form-control row-currency"><option value="TRY">TRY</option><option value="USD">USD</option></select></td>'+
    '<td><select name="provider_item[]" class="form-control provider"><option value="">Seçiniz</option>'+providerOptions+'</select></td>'+
    '<td><select name="item_vat[]" class="form-control vat">'+
       '<option value="0">%0</option><option value="1">%1</option><option value="10">%10</option><option value="20">%20</option>'+
    '</select></td>'+
    '<td><input type="text" name="description[]" class="form-control desc"></td>'+
    '<td class="row-total">0</td>'+
    '<td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>';
  tbody.appendChild(tr);
  initRow(tr);
  updateTotal();
}
function prodChanged(){
  var select=this;
  var tr=select.closest('tr');
  var custom=tr.querySelector('.custom');
  var opt=select.options[select.selectedIndex];
  if(select.value==='__new__'){
    custom.classList.remove('d-none');
    tr.querySelector('.price').value='';
    tr.querySelector('.row-currency').value='TRY';
    tr.querySelector('.vat').value='0';
  }else{
    custom.classList.add('d-none');
    if(opt.dataset){
      tr.querySelector('.price').value=opt.dataset.price||'';
      tr.querySelector('.row-currency').value=opt.dataset.currency||'TRY';
      tr.querySelector('.vat').value=opt.dataset.vat||'0';
    }
  }
  updateTotal();
}
function updateTotal(){
  var rate = <?= $usdRate ? $usdRate : 0 ?>;
  var totalTl = 0;
  var vatTl = 0;
  document.querySelectorAll('#items tbody tr').forEach(function(tr){
    var q = parseFloat(tr.querySelector('.qty').value)||0;
    var p = parseFloat(tr.querySelector('.price').value)||0;
    var v = parseFloat(tr.querySelector('.vat').value)||0;
    var cur = tr.querySelector('.row-currency').value;
    var lineSub = q*p;
    var lineVat = lineSub*v/100;
    var lineTl = cur==='USD' ? (lineSub+lineVat)*rate : (lineSub+lineVat);
    tr.querySelector('.row-total').innerText=lineTl.toFixed(2);
    totalTl += cur==='USD' ? lineSub*rate : lineSub;
    vatTl += cur==='USD' ? lineVat*rate : lineVat;
  });
  document.getElementById('total').innerText=totalTl.toFixed(2);
  document.getElementById('vat_t').innerText=vatTl.toFixed(2);
  document.getElementById('grand').innerText=(totalTl+vatTl).toFixed(2);
}

document.querySelectorAll('#items tbody tr').forEach(initRow);
document.getElementById('addRow').addEventListener('click',addRow);
updateTotal();

document.querySelector('form').addEventListener('submit',function(e){
  var warn=false;
  document.querySelectorAll('#items tbody tr').forEach(function(tr){
    if(!tr.querySelector('.provider').value) warn=true;
  });
  if(warn && !confirm('Bazı satırlarda sağlayıcı seçilmedi, devam edilsin mi?')){
    e.preventDefault();
    return;
  }
  newProducts=[];
  document.querySelectorAll('#items tbody tr').forEach(function(tr){
    if(tr.querySelector('.prod').value==='__new__'){
      var name=tr.querySelector('.custom').value.trim();
      if(!name) return;
      newProducts.push({
        name:name,
        unit:tr.querySelector('.unit').value,
        vat_rate:tr.querySelector('.vat').value,
        price:tr.querySelector('.price').value,
        currency:tr.querySelector('.row-currency').value
      });
    }
  });
  document.getElementById('new_products_json').value=JSON.stringify(newProducts);
});
</script>
<div class="mb-5"></div>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$providers = $pdo->query('SELECT id,name FROM providers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$usdRate = getUsdRate($pdo);

if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['provider_id']) && !empty($_POST['item_name'])){
    $providerId = (int)$_POST['provider_id'];
    $ins = $pdo->prepare('INSERT INTO provider_purchases(provider_id,item_name,quantity,unit,unit_price,vat_rate,currency,purchase_date,payment_date,price_try,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach($_POST['item_name'] as $i=>$name){
        if(trim($name)=='') continue;
        $qty = (int)($_POST['quantity'][$i] ?? 1);
        $unit = $_POST['unit'][$i] ?? '';
        $price = (float)($_POST['unit_price'][$i] ?? 0);
        $vat = (float)($_POST['vat_rate'][$i] ?? 0);
        $cur = $_POST['currency'][$i] ?? 'TRY';
        $pdate = $_POST['purchase_date'][$i] ?: date('Y-m-d');
        $paydate = $_POST['payment_date'][$i] ?: $pdate;
        $line = $qty*$price;
        $lineVat = $line*$vat/100;
        $try = $cur==='USD' ? ($line+$lineVat)*$usdRate : ($line+$lineVat);
        $ins->execute([$providerId,$name,$qty,$unit,$price,$vat,$cur,$pdate,$paydate,$try,$_POST['notes'][$i] ?? '']);
    }
    $_SESSION['message'] = 'Satın alım kaydedildi';
    header('Location: providers.php');
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Ürün Satın Al</h1>
<form method="post">
<div class="mb-3">
 <label class="form-label">Sağlayıcı</label>
 <select name="provider_id" class="form-control" required>
  <option value="">Seçiniz</option>
  <?php foreach($providers as $pr): ?>
   <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['name']) ?></option>
  <?php endforeach; ?>
 </select>
</div>
<table class="table" id="items">
 <thead>
  <tr><th>Ürün</th><th>Miktar</th><th>Birim</th><th>Birim Fiyat</th><th>KDV</th><th>Döviz</th><th>Satın Alma</th><th>Ödeme</th><th>Not</th><th></th></tr>
 </thead>
 <tbody></tbody>
</table>
<button type="button" class="btn btn-secondary mb-3" id="addRow">Satır Ekle</button>
<button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<script>
function addRow(){
  var tr=document.createElement('tr');
  tr.innerHTML='<td><input type="text" name="item_name[]" class="form-control" required></td>'+
    '<td><input type="number" name="quantity[]" value="1" class="form-control"></td>'+
    '<td><select name="unit[]" class="form-control"><option value="ADET">ADET</option><option value="AY">AY</option><option value="YIL">YIL</option></select></td>'+
    '<td><input type="text" name="unit_price[]" class="form-control"></td>'+
    '<td><select name="vat_rate[]" class="form-control"><option value="0">%0</option><option value="10">%10</option><option value="20">%20</option></select></td>'+
    '<td><select name="currency[]" class="form-control"><option value="TRY">TRY</option><option value="USD">USD</option></select></td>'+
    '<td><input type="date" name="purchase_date[]" class="form-control" value="<?= date("Y-m-d") ?>"></td>'+
    '<td><input type="date" name="payment_date[]" class="form-control"></td>'+
    '<td><input type="text" name="notes[]" class="form-control"></td>'+
    '<td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest(\'tr\').remove();">X</button><br><small class="price-info text-muted"></small></td>';
  tr.querySelectorAll('input,select').forEach(function(el){el.addEventListener('input',function(){updateRow(tr);});});
  updateRow(tr);
  document.querySelector('#items tbody').appendChild(tr);
}
function updateRow(tr){
  var qty=parseFloat(tr.querySelector('[name="quantity[]"]').value)||1;
  var price=parseFloat(tr.querySelector('[name="unit_price[]"]').value)||0;
  var vat=parseFloat(tr.querySelector('[name="vat_rate[]"]').value)||0;
  var cur=tr.querySelector('[name="currency[]"]').value;
  var rate=cur==='USD'?<?= $usdRate ?>:1;
  var subtotal=qty*price*rate;
  var vatTl=subtotal*vat/100;
  var total=subtotal+vatTl;
  tr.querySelector('.price-info').textContent='Birim: '+(price*rate).toFixed(2)+' TL, KDV: '+vatTl.toFixed(2)+' TL, Toplam: '+total.toFixed(2)+' TL';
}
addRow();
document.getElementById('addRow').addEventListener('click',addRow);
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM providers WHERE id=?');
$stmt->execute([$id]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$provider){
    header('Location: providers.php');
    exit;
}
$usdRate = getUsdRate($pdo);
$totPurch = (float)$pdo->query("SELECT SUM(price_try) FROM provider_purchases WHERE provider_id={$id}")->fetchColumn();
$totPay = (float)$pdo->query("SELECT SUM(amount_try) FROM provider_payments WHERE provider_id={$id}")->fetchColumn();
$balance = $totPurch - $totPay;
if($balance < 0) $balance = 0;
if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['item_name'])){
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
        $ins->execute([$id,$name,$qty,$unit,$price,$vat,$cur,$pdate,$paydate,$try,$_POST['notes'][$i] ?? '']);
    }
    $_SESSION['message']='Satın alım kaydedildi';
    header('Location: provider.php?id='.$id);
    exit;
}
$purchasesStmt = $pdo->prepare('SELECT * FROM provider_purchases WHERE provider_id=? ORDER BY purchase_date DESC');
$purchasesStmt->execute([$id]);
$purchases = $purchasesStmt->fetchAll(PDO::FETCH_ASSOC);
$payStmt = $pdo->prepare('SELECT * FROM provider_payments WHERE provider_id=? ORDER BY pay_date DESC');
$payStmt->execute([$id]);
$payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__.'/includes/header.php';
?>
<h1>Tedarikçi: <?= htmlspecialchars($provider['name']) ?><?php if($provider['website']): ?> - <a href="<?= htmlspecialchars($provider['website']) ?>" target="_blank"><?= htmlspecialchars($provider['website']) ?></a><?php endif; ?></h1>
<p><strong>Bakiye:</strong> <?= number_format($balance,2,',','.') ?> TL</p>
<a href="provider_payment.php?provider_id=<?= $id ?>" class="btn btn-primary mb-3">Ödeme Yap</a>
<table class="table table-bordered">
 <thead>
  <tr>
   <th>Ürün</th><th>Miktar</th><th>Birim</th><th>Birim Fiyat</th><th>KDV</th><th>Döviz</th><th>Satın Alma</th><th>Ödeme</th><th>Tutar (TL)</th><th>İşlem</th>
  </tr>
 </thead>
 <tbody>
  <?php foreach($purchases as $p): ?>
  <tr>
   <td><?= htmlspecialchars($p['item_name']) ?></td>
   <td><?= $p['quantity'] ?></td>
   <td><?= htmlspecialchars($p['unit']) ?></td>
   <td><?= number_format($p['unit_price'],2,',','.') ?></td>
   <td><?= $p['vat_rate'] ?>%</td>
   <td><?= htmlspecialchars($p['currency']) ?></td>
   <td><?= date('d.m.Y',strtotime($p['purchase_date'])) ?></td>
   <td><?= date('d.m.Y',strtotime($p['payment_date'])) ?></td>
   <td><?= number_format($p['price_try'],2,',','.') ?></td>
   <td>
     <a href="purchase_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
     <a href="purchase_delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
   </td>
  </tr>
  <?php endforeach; ?>
 </tbody>
</table>
<?php if($payments): ?>
<h2>Ödeme Geçmişi</h2>
<table class="table table-bordered">
 <thead><tr><th>Tarih</th><th>Tutar (TL)</th><th>Döviz</th><th>Not</th><th>İşlem</th></tr></thead>
 <tbody>
  <?php foreach($payments as $pay): ?>
  <tr>
   <td><?= date('d.m.Y',strtotime($pay['pay_date'])) ?></td>
   <td><?= number_format($pay['amount_try'],2,',','.') ?></td>
   <td><?= htmlspecialchars($pay['currency']) ?> <?= number_format($pay['amount_orig'],2,',','.') ?></td>
   <td><?= htmlspecialchars($pay['notes']) ?></td>
   <td>
     <a href="provider_payment_edit.php?id=<?= $pay['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
     <a href="provider_payment_delete.php?id=<?= $pay['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
   </td>
  </tr>
  <?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>
<hr>
<h2>Yeni Satın Alım</h2>
<form method="post">
<table class="table" id="items">
 <thead>
  <tr><th>Ürün</th><th>Miktar</th><th>Birim</th><th>Birim Fiyat</th><th>KDV</th><th>Döviz</th><th>Satın Alma Tarihi</th><th>Ödeme Tarihi</th><th>Not</th><th></th></tr>
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

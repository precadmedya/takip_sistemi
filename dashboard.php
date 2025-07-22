<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$events = [];
$monthTotal = 0;
$rows = $pdo->query("SELECT s.id,s.site_name,c.full_name,s.due_date,
 (s.price_try*(1+s.vat_rate/100) - IFNULL((SELECT SUM(amount_try) FROM payments WHERE service_id=s.id),0)) AS remain
 FROM services s JOIN customers c ON s.customer_id=c.id")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){
    if($r['remain']<=0) continue;
    $diff = (strtotime($r['due_date']) - time())/86400;
    $color = '#198754'; // green
    if($diff<0){ $color='#dc3545'; }
    elseif($diff<=14){ $color='#fd7e14'; }
    elseif($diff<=30){ $color='#ffc107'; }
    $events[] = [
        'title'=> $r['full_name']."\n".$r['site_name']."\n".number_format($r['remain'],2,',','.').' ₺',
        'start'=> $r['due_date'],
        'url'=> '/service.php?id='.$r['id'],
        'backgroundColor'=>$color,
        'borderColor'=>$color
    ];
    if(date('Y-m',strtotime($r['due_date']))==date('Y-m')){
        $monthTotal += $r['remain'];
    }
}
$overallTotal = (float)$pdo->query("SELECT SUM(s.price_try*(1+s.vat_rate/100) - IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.service_id=s.id),0)) FROM services s")->fetchColumn();
$customerCount = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$serviceCount = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$topServices = $pdo->query("SELECT service_type, COUNT(*) c FROM services GROUP BY service_type ORDER BY c DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recent = $pdo->query("SELECT s.id,c.full_name,s.site_name FROM services s JOIN customers c ON s.customer_id=c.id ORDER BY s.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$rateRow = $pdo->query("SELECT rate_date, usd_try FROM exchange_rates ORDER BY rate_date DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
include __DIR__.'/includes/header.php';
?>
<h1>Anasayfa</h1>
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-bg-light mb-3"><div class="card-body"><h5 class="card-title">Bu Ay Alınacak</h5><p class="card-text fw-bold"><?= number_format($monthTotal,2,',','.') ?> ₺</p></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light mb-3"><div class="card-body"><h5 class="card-title">Toplam Alacak</h5><p class="card-text fw-bold"><?= number_format($overallTotal,2,',','.') ?> ₺</p></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light mb-3"><div class="card-body"><h5 class="card-title">Müşteri Sayısı</h5><p class="card-text fw-bold"><?= $customerCount ?></p></div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light mb-3"><div class="card-body"><h5 class="card-title">Hizmet Sayısı</h5><p class="card-text fw-bold"><?= $serviceCount ?></p></div></div>
  </div>
  <?php if ($rateRow): ?>
  <div class="col-md-3">
    <div class="card text-bg-light mb-3"><div class="card-body"><h5 class="card-title">Güncel Kur</h5><p class="card-text fw-bold"><?= number_format($rateRow['usd_try'],4,',','.') ?> (<?= htmlspecialchars($rateRow['rate_date']) ?>)</p></div></div>
  </div>
  <?php endif; ?>
</div>
<h2>Takvim</h2>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
<div id="calendar"></div>
<div class="modal fade" id="eventModal" tabindex="-1">
 <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detay</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="eventModalBody"></div></div></div>
</div>
<h2 class="mt-5">Analizler</h2>
<div class="row">
 <div class="col-md-6">
  <h4>En Çok Satan Hizmetler</h4>
  <ul>
   <?php foreach($topServices as $t): ?>
    <li><?= htmlspecialchars($t['service_type']) ?> (<?= $t['c'] ?>)</li>
   <?php endforeach; ?>
  </ul>
 </div>
 <div class="col-md-6">
  <h4>Son Eklenen Hizmetler</h4>
  <ul>
   <?php foreach($recent as $r): ?>
    <li><a href="/service.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name'].' - '.$r['site_name']) ?></a></li>
   <?php endforeach; ?>
  </ul>
 </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
 var calendar=new FullCalendar.Calendar(document.getElementById('calendar'),{
    initialView:'dayGridMonth',
    events: <?= json_encode($events) ?>,
    eventClick:function(info){
      info.jsEvent.preventDefault();
      document.getElementById('eventModalBody').innerHTML = info.event.title.replace(/\n/g,'<br>');
      new bootstrap.Modal(document.getElementById('eventModal')).show();
    }
 });
 calendar.render();
});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

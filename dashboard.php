<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$startMonth = sprintf('%04d-%02d-01', $year, $month);
$endMonth = date('Y-m-t', strtotime($startMonth));

$events = [];
$monthTotal = 0;
$rows = $pdo->query("SELECT s.id,s.site_name,c.full_name,s.due_date,
 (s.price_try*(1+s.vat_rate/100) - IFNULL((SELECT SUM(amount_try) FROM payments WHERE service_id=s.id),0)) AS remain
 FROM services s JOIN customers c ON s.customer_id=c.id")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){
    if($r['remain']<=0) continue;
    if($r['due_date'] >= $startMonth && $r['due_date'] <= $endMonth){
        $day = (int)date('j', strtotime($r['due_date']));
        $events[$day][] = $r;
        $monthTotal += $r['remain'];
    } elseif(date('Y-m',strtotime($r['due_date']))==date('Y-m')) {
        $monthTotal += $r['remain'];
    }
}

$overallTotal = (float)$pdo->query("SELECT SUM(s.price_try*(1+s.vat_rate/100) - IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.service_id=s.id),0)) FROM services s")->fetchColumn();
$customerCount = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$serviceCount = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$topServices = $pdo->query("SELECT service_type, COUNT(*) c FROM services GROUP BY service_type ORDER BY c DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recent = $pdo->query("SELECT s.id,c.full_name,s.site_name FROM services s JOIN customers c ON s.customer_id=c.id ORDER BY s.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$rateRow = $pdo->query("SELECT rate_date, usd_try FROM exchange_rates ORDER BY rate_date DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$upcoming = $pdo->query("SELECT s.id,s.site_name,c.full_name,s.due_date FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.due_date >= CURDATE() ORDER BY s.due_date ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$monthName = $months[$month-1];
$prevM = $month-1; $prevY = $year; if($prevM<1){$prevM=12;$prevY--;}
$nextM = $month+1; $nextY = $year; if($nextM>12){$nextM=1;$nextY++;}
$startDow = (int)date('N', strtotime($startMonth)); // 1=Mon
$daysInMonth = (int)date('t', strtotime($startMonth));
$weeks = ceil(($startDow-1 + $daysInMonth)/7);

$eventsJs = [];
foreach($events as $d=>$evs){
    foreach($evs as $e){
        $eventsJs[$d][] = ['site'=>$e['site_name'],'customer'=>$e['full_name'],'due'=>$e['due_date']];
    }
}

include __DIR__.'/includes/header.php';
?>
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
<div class="row">
  <div class="col-lg-8 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div>
        <a class="btn btn-sm btn-outline-secondary" href="?y=<?= $prevY ?>&m=<?= $prevM ?>">&lt;</a>
        <strong class="mx-2"><?= $monthName.' '.$year ?></strong>
        <a class="btn btn-sm btn-outline-secondary" href="?y=<?= $nextY ?>&m=<?= $nextM ?>">&gt;</a>
      </div>
      <a class="btn btn-sm btn-outline-primary" href="?">Bugün</a>
    </div>
    <table class="table calendar table-bordered text-center">
      <thead><tr><th>Pzt</th><th>Sal</th><th>Çar</th><th>Per</th><th>Cum</th><th>Cmt</th><th>Paz</th></tr></thead>
      <tbody>
      <?php
      $day = 1 - ($startDow-1);
      for($w=0;$w<$weeks;$w++):
          echo "<tr>";
          for($d=1;$d<=7;$d++):
              if($day<1 || $day>$daysInMonth){
                  echo '<td class="bg-light"></td>';
              } else {
                  $has = !empty($events[$day]);
                  echo '<td class="calendar-day" data-day="'.$day.'">';
                  echo '<div class="day-number">'.$day.'</div>';
                  if($has){
                      foreach($events[$day] as $ev){
                          $diff = floor((strtotime($ev['due_date'])-time())/86400);
                          $cls = $diff<0?'bg-danger':($diff<=14?'bg-orange':($diff<=30?'bg-warning':'bg-success'));
                          echo '<div class="event '.$cls.'" title="'.htmlspecialchars($ev['site_name'],ENT_QUOTES).'"></div>';
                      }
                  }
                  echo '</td>';
              }
              $day++;
          endfor;
          echo "</tr>";
      endfor;
      ?>
      </tbody>
    </table>
  </div>
  <div class="col-lg-4">
    <input type="text" id="search" class="form-control mb-2" placeholder="Veriler içinde arama yap">
    <div class="list-group" id="upcomingList" style="max-height:400px;overflow:auto;">
      <?php foreach($upcoming as $u):
            $diff=floor((strtotime($u['due_date'])-time())/86400);
            $cls=$diff<0?'bg-danger':($diff<=14?'bg-orange':($diff<=30?'bg-warning':'bg-success'));
            $txt=$diff>=0?'+'.$diff.' gün kaldı':abs($diff).' gün geçti';
      ?>
      <a href="service.php?id=<?= $u['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-search="<?= strtolower($u['site_name'].' '.$u['full_name']) ?>">
        <span>
          <strong><?= htmlspecialchars($u['site_name']) ?></strong><br>
          <small><?= htmlspecialchars($u['full_name']) ?></small>
        </span>
        <span class="badge text-light <?= $cls ?>"><?= $txt ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="modal fade" id="dayModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Gün Detayları</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="dayModalBody"></div>
  </div></div>
</div>
<h2 class="mt-5">Analizler</h2>
<div class="row">
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">En Çok Satan Hizmetler</div>
      <ul class="list-group list-group-flush">
        <?php foreach($topServices as $t): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= htmlspecialchars($t['service_type']) ?>
          <span class="badge bg-secondary"><?= $t['c'] ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">Son Eklenen Hizmetler</div>
      <ul class="list-group list-group-flush">
        <?php foreach($recent as $r): ?>
        <li class="list-group-item"><a href="service.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name'].' - '.$r['site_name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<style>
.calendar{background:#fff;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,0.1);} 
.calendar-day{height:80px;vertical-align:top;cursor:pointer;transition:transform .2s;}
.calendar-day:hover{transform:scale(1.05);} 
.day-number{font-weight:500;text-align:right;}
.event{height:6px;border-radius:3px;margin-top:2px;}
.bg-orange{background-color:#fd7e14!important;color:#fff;}
</style>
<script>
var events = <?= json_encode($eventsJs) ?>;
document.querySelectorAll('.calendar-day').forEach(function(td){
  td.addEventListener('click',function(){
    var d=this.dataset.day;
    if(!events[d]) return;
    var html='';
    events[d].forEach(function(ev){
      var due=new Date(ev.due);
      var today=new Date();
      today.setHours(0,0,0,0);
      var diff=Math.floor((due-today)/86400000);
      var txt=(diff>=0?"+"+diff+" gün kaldı":Math.abs(diff)+" gün geçti");
      html+='<p><strong>'+ev.site+'</strong> - '+ev.customer+' ('+txt+')</p>';
    });
    document.getElementById('dayModalBody').innerHTML=html;
    new bootstrap.Modal(document.getElementById('dayModal')).show();
  });
});
var search=document.getElementById('search');
search.addEventListener('input',function(){
  var t=this.value.toLowerCase();
  document.querySelectorAll('#upcomingList [data-search]').forEach(function(a){
    a.style.display=a.dataset.search.includes(t)?'':'none';
  });
});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

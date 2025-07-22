<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$startMonth = sprintf('%04d-%02d-01',$year,$month);
$endMonth = date('Y-m-t',strtotime($startMonth));
$rows = $pdo->query("SELECT pp.*, pr.name FROM provider_purchases pp JOIN providers pr ON pp.provider_id=pr.id")->fetchAll(PDO::FETCH_ASSOC);
$events=[];$monthTotal=0;
foreach($rows as $r){
    if($r['payment_date'] >= $startMonth && $r['payment_date'] <= $endMonth){
        $d=(int)date('j',strtotime($r['payment_date']));
        $events[$d][]=$r;
        $monthTotal+=$r['price_try'];
    } elseif(date('Y-m',strtotime($r['payment_date']))==date('Y-m')){
        $monthTotal+=$r['price_try'];
    }
}
$months=['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$monthName=$months[$month-1];
$prevM=$month-1;$prevY=$year;if($prevM<1){$prevM=12;$prevY--;}
$nextM=$month+1;$nextY=$year;if($nextM>12){$nextM=1;$nextY++;}
$eventsJs=[];
foreach($events as $d=>$evs){
    foreach($evs as $e){
        $eventsJs[$d][]=['name'=>$e['name'],'item'=>$e['item_name'],'due'=>$e['payment_date']];
    }
}
include __DIR__.'/includes/header.php';
?>
<h1>Tedarikçi Ödemeleri</h1>
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
$startDow=(int)date('N',strtotime($startMonth));
$daysInMonth=(int)date('t',strtotime($startMonth));
$weeks=ceil(($startDow-1+$daysInMonth)/7);
$day=1-($startDow-1);
for($w=0;$w<$weeks;$w++){
    echo "<tr>";
    for($d=1;$d<=7;$d++){
        if($day<1||$day>$daysInMonth){
            echo '<td class="bg-light"></td>';
        }else{
            $has=!empty($events[$day]);
            echo '<td class="calendar-day" data-day="'.$day.'">';
            echo '<div class="day-number">'.$day.'</div>';
            if($has){
                foreach($events[$day] as $ev){
                    echo '<div class="event bg-primary" title="'.htmlspecialchars($ev['name'],ENT_QUOTES).'"></div>';
                }
            }
            echo '</td>';
        }
        $day++;}
    echo "</tr>";
}
?>
 </tbody>
</table>
<div class="modal fade" id="dayModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title">Gün Detayları</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
 <div class="modal-body" id="dayModalBody"></div>
</div></div></div>
<style>.calendar{background:#fff;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,0.1);} .calendar-day{height:80px;vertical-align:top;cursor:pointer;transition:transform .2s;} .calendar-day:hover{transform:scale(1.05);} .day-number{text-align:right;font-weight:500;} .event{height:6px;border-radius:3px;margin-top:2px;background-color:#0d6efd;}</style>
<script>
var events = <?= json_encode($eventsJs) ?>;
document.querySelectorAll('.calendar-day').forEach(function(td){
 td.addEventListener('click',function(){
  var d=this.dataset.day; if(!events[d]) return; var html='';
  events[d].forEach(function(ev){ html+='<p><strong>'+ev.item+'</strong> - '+ev.name+'</p>'; });
  document.getElementById('dayModalBody').innerHTML=html;
  new bootstrap.Modal(document.getElementById('dayModal')).show();
 });
});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

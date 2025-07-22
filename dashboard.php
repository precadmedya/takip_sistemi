<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

$usdRate = getUsdRate($pdo);

$monthStmt = $pdo->query("SELECT s.id, s.site_name, s.service_type, s.due_date, c.full_name,
    (s.price_try*(1+s.vat_rate/100) - IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.service_id=s.id),0)) AS remain
    FROM services s JOIN customers c ON s.customer_id=c.id
    WHERE MONTH(s.due_date)=MONTH(CURDATE()) AND YEAR(s.due_date)=YEAR(CURDATE())
    ORDER BY s.due_date");
$monthServices = $monthStmt->fetchAll(PDO::FETCH_ASSOC);
$monthTotal = 0;
$events = [];
foreach($monthServices as $m){
    if($m['remain'] <= 0) continue;
    $monthTotal += $m['remain'];
    $day = (int)date('j', strtotime($m['due_date']));
    $events[$day][] = $m;
}
$allStmt = $pdo->query("SELECT SUM(s.price_try*(1+s.vat_rate/100) - IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.service_id=s.id),0)) FROM services s");
$overallTotal = (float)$allStmt->fetchColumn();
$customerCount = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$serviceCount = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();

$year = date('Y');
$month = date('n');
$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first = date('N', strtotime("$year-$month-01"));

include __DIR__.'/includes/header.php';
?>
<h1>Anasayfa</h1>
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-bg-light mb-3">
      <div class="card-body">
        <h5 class="card-title">Bu Ay Alınacak</h5>
        <p class="card-text fw-bold"><?= number_format($monthTotal,2,',','.') ?> ₺</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light mb-3">
      <div class="card-body">
        <h5 class="card-title">Toplam Alacak</h5>
        <p class="card-text fw-bold"><?= number_format($overallTotal,2,',','.') ?> ₺</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light mb-3">
      <div class="card-body">
        <h5 class="card-title">Müşteri Sayısı</h5>
        <p class="card-text fw-bold"><?= $customerCount ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-bg-light mb-3">
      <div class="card-body">
        <h5 class="card-title">Hizmet Sayısı</h5>
        <p class="card-text fw-bold"><?= $serviceCount ?></p>
      </div>
    </div>
  </div>
</div>
<h2>Takvim</h2>
<table class="table table-bordered text-center">
 <thead>
  <tr>
    <th>Pzt</th><th>Sal</th><th>Çar</th><th>Per</th><th>Cum</th><th>Cmt</th><th>Paz</th>
  </tr>
 </thead>
 <tbody>
<?php
$day=1;
$cell=1;
for($row=1;$row<=6;$row++){
    echo "<tr>";
    for($col=1;$col<=7;$col++){
        if($cell<$first || $day>$days){
            echo "<td></td>";
        }else{
            echo "<td><div class=\"fw-bold\">$day</div>";
            if(isset($events[$day])){
                foreach($events[$day] as $ev){
                    $amt = number_format($ev['remain'],2,',','.');
                    echo "<div class=\"small\"><a href='/service.php?id={$ev['id']}'>{$ev['full_name']}<br>{$ev['site_name']}<br>{$amt} ₺</a></div>";
                }
            }
            echo "</td>";
            $day++;
        }
        $cell++;
    }
    echo "</tr>";
    if($day>$days) break;
}
?>
 </tbody>
</table>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';

function fetchTodayRate(PDO $pdo): float{
    $xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
    $rate = 0;
    if($xml){
        foreach($xml->Currency as $cur){
            if((string)$cur['CurrencyCode']=='USD'){
                $rate = (float)str_replace(',', '.', (string)$cur->BanknoteSelling);
                break;
            }
        }
    }
    if($rate){
        $stmt=$pdo->prepare('SELECT id FROM exchange_rates WHERE rate_date=CURDATE()');
        $stmt->execute();
        if($stmt->fetchColumn()){
            $u=$pdo->prepare('UPDATE exchange_rates SET usd_try=? WHERE rate_date=CURDATE()');
            $u->execute([$rate]);
        }else{
            $i=$pdo->prepare('INSERT INTO exchange_rates(rate_date,usd_try) VALUES(CURDATE(),?)');
            $i->execute([$rate]);
        }
    }
    return $rate;
}

$rate = fetchTodayRate($pdo);
if($rate){
    $svc = $pdo->query('SELECT id FROM services');
    while($s = $svc->fetch(PDO::FETCH_ASSOC)){
        $its = $pdo->prepare('SELECT * FROM service_items WHERE service_id=?');
        $its->execute([$s['id']]);
        $t=0;$v=0;
        foreach($its->fetchAll(PDO::FETCH_ASSOC) as $it){
            $sub=$it['quantity']*$it['unit_price'];
            $vat=$sub*$it['vat_rate']/100;
            if($it['currency']==='USD'){ $sub*=$rate; $vat*=$rate; }
            $t+=$sub; $v+=$vat;
        }
        $total=$t+$v;
        $pdo->prepare('UPDATE services SET price=?,price_try=? WHERE id=?')->execute([$total,$total,$s['id']]);
    }
    $_SESSION['message']='Kurlar ve fiyatlar güncellendi';
}else{
    $_SESSION['message']='Kur alınamadı';
}
header('Location: '.($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));

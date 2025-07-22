<?php
function getUsdRate(PDO $pdo): float {
    $stmt = $pdo->query("SELECT usd_try FROM exchange_rates ORDER BY rate_date DESC LIMIT 1");
    $rate = (float)$stmt->fetchColumn();
    if($rate){
        return $rate;
    }
    $xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
    if($xml){
        foreach($xml->Currency as $cur){
            if((string)$cur['CurrencyCode']=='USD'){
                $rate = (float)str_replace(',', '.', (string)$cur->BanknoteSelling);
                if($rate){
                    $ins = $pdo->prepare('INSERT INTO exchange_rates(rate_date,usd_try) VALUES (CURDATE(), ?)');
                    $ins->execute([$rate]);
                }
                break;
            }
        }
    }
    return $rate ?: 0.0;
}
?>

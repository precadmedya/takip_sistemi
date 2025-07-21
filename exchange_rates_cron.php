<?php
require __DIR__.'/includes/db.php';

$xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
if ($xml) {
    foreach ($xml->Currency as $currency) {
        if ((string)$currency['CurrencyCode'] === 'USD') {
            $rate = str_replace(',', '.', (string)$currency->BanknoteSelling);
            $stmt = $pdo->prepare("INSERT INTO exchange_rates (rate_date, usd_try) VALUES (CURDATE(), ?)");
            $stmt->execute([$rate]);
            echo "Günlük kur kaydedildi: $rate";
            break;
        }
    }
} else {
    echo "Kur verisi alınamadı";
}

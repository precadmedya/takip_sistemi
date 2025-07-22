<?php
require __DIR__.'/includes/db.php';

$xml = @simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
if ($xml) {
    foreach ($xml->Currency as $currency) {
        if ((string)$currency['CurrencyCode'] === 'USD') {
            $rate = str_replace(',', '.', (string)$currency->BanknoteSelling);
            $exists = $pdo->prepare('SELECT id FROM exchange_rates WHERE rate_date = CURDATE()');
            $exists->execute();
            if (!$exists->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO exchange_rates (rate_date, usd_try) VALUES (CURDATE(), ?)");
                $stmt->execute([$rate]);
                echo "Günlük kur kaydedildi: $rate";
            } else {
                echo "Kur zaten kayıtlı";
            }
            break;
        }
    }
} else {
    echo "Kur verisi alınamadı";
}

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

function getSmtpSettings(PDO $pdo): array {
    $defaults = include __DIR__ . '/../config/config.php';
    $settings = [];
    $stmt = $pdo->prepare("SELECT `key`, value FROM settings WHERE `key` IN ('smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email')");
    $stmt->execute();
    foreach($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $k=>$v){
        $settings[$k] = $v;
    }
    return [
        'host' => $settings['smtp_host'] ?? $defaults['smtp']['host'],
        'port' => (int)($settings['smtp_port'] ?? $defaults['smtp']['port']),
        'encryption' => $settings['smtp_encryption'] ?? $defaults['smtp']['encryption'],
        'username' => $settings['smtp_user'] ?? $defaults['smtp']['username'],
        'password' => $settings['smtp_pass'] ?? $defaults['smtp']['password'],
        'from_name' => $settings['smtp_from_name'] ?? $defaults['smtp']['from_name'],
        'from_email' => $settings['smtp_from_email'] ?? $defaults['smtp']['from_email']
    ];
}

function sendMail(PDO $pdo, string $to, string $subject, string $body, string &$error = ''): bool {
    require_once __DIR__ . '/PHPMailer.php';
    $smtp = getSmtpSettings($pdo);
    $mail = new PHPMailer();
    $mail->Host = $smtp['host'];
    $mail->Port = $smtp['port'];
    $mail->SMTPSecure = $smtp['encryption'];
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];
    $mail->From = $smtp['from_email'];
    $mail->FromName = $smtp['from_name'];
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->addAddress($to);
    $ok = $mail->send();
    if(!$ok){
        $error = $mail->ErrorInfo ?: 'Bilinmeyen hata';
    }
    return $ok;
}
?>

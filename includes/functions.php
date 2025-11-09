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

function getEmailSettings(PDO $pdo): array {
    $defaults = include __DIR__ . '/../config/config.php';
    $settings = [];
    $stmt = $pdo->prepare("SELECT `key`, value FROM settings WHERE `key` IN ('smtp_host','smtp_port','smtp_encryption','smtp_user','smtp_pass','smtp_from_name','smtp_from_email','mail_logo','smtp_timeout','smtp_reminder_copy')");
    $stmt->execute();
    foreach($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $k=>$v){
        $settings[$k] = is_string($v) ? trim($v) : $v;
    }
    return [
        'host' => $settings['smtp_host'] ?? $defaults['smtp']['host'],
        'port' => (int)($settings['smtp_port'] ?? $defaults['smtp']['port']),
        'encryption' => $settings['smtp_encryption'] ?? $defaults['smtp']['encryption'],
        'username' => $settings['smtp_user'] ?? $defaults['smtp']['username'],
        'password' => $settings['smtp_pass'] ?? $defaults['smtp']['password'],
        'from_name' => $settings['smtp_from_name'] ?? $defaults['smtp']['from_name'],
        'from_email' => $settings['smtp_from_email'] ?? $defaults['smtp']['from_email'],
        'logo' => isset($settings['mail_logo']) ? trim($settings['mail_logo']) : '',
        'timeout' => (int)($settings['smtp_timeout'] ?? ($defaults['smtp']['timeout'] ?? 20)),
        'reminder_copy_email' => $settings['smtp_reminder_copy'] ?? ($defaults['smtp']['reminder_copy_email'] ?? '')
    ];
}

/**
 * @param string|array $to
 */
function sendMail(PDO $pdo, $to, string $subject, string $body, string &$error = '', ?int $serviceId = null, ?int $clientId = null): bool {
    require_once __DIR__ . '/PHPMailer.php';
    $smtp = getEmailSettings($pdo);
    $smtp['encryption'] = strtolower((string)$smtp['encryption']);
    if (!in_array($smtp['encryption'], ['ssl','tls','none'], true)) {
        $smtp['encryption'] = 'ssl';
    }
    $fromEmail = trim($smtp['from_email'] ?? '');
    $fromName  = trim($smtp['from_name'] ?? '');
    if ($fromEmail === '' && !empty($smtp['username'])) {
        $fromEmail = $smtp['username'];
    }
    if ($fromEmail === '') $fromEmail = 'muhasebe@precadmedya.com.tr';
    if ($fromName === '')  $fromName = 'Precad Medya';
    $fromName = preg_replace('/[\r\n]+/', ' ', $fromName);
    $subject = trim($subject);
    if ($subject === '') $subject = 'Ödeme Hatırlatma';

    $recipientList = [];
    if (is_array($to)) {
        foreach ($to as $addr) {
            if (!is_string($addr)) continue;
            $addr = trim($addr);
            if ($addr !== '') {
                $recipientList[] = $addr;
            }
        }
    } elseif (is_string($to)) {
        foreach (preg_split('/[,;]+/', $to) as $addr) {
            $addr = trim($addr);
            if ($addr !== '') {
                $recipientList[] = $addr;
            }
        }
    }
    $recipientList = array_values(array_unique($recipientList));
    if (empty($recipientList)) {
        $error = 'Alıcı e-posta adresi bulunamadı.';
        return false;
    }
    foreach ($recipientList as $addr) {
        if(!filter_var($addr, FILTER_VALIDATE_EMAIL)){
            $error = 'Alıcı e-posta adresi geçersiz: '.$addr;
            return false;
        }
    }

    $mail = new PHPMailer();
    $mail->Host       = trim((string)$smtp['host']);
    $mail->Port       = $smtp['port'];
    $mail->SMTPSecure = $smtp['encryption'];
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->From       = $fromEmail;
    $mail->FromName   = $fromName;
    $mail->Subject    = $subject;
    $mail->Timeout    = max(5, (int)$smtp['timeout']);
    $heloDomain = 'localhost';
    if(strpos($fromEmail,'@') !== false){
        $heloDomain = substr(strrchr($fromEmail,'@'),1);
    }
    $mail->HeloDomain = $heloDomain;
    $mail->addReplyTo($fromEmail);
    if($mail->Host===''){
        $error = 'SMTP sunucusu tanımlı değil.';
        return false;
    }
    if($smtp['logo']){
        $src = $smtp['logo'];
        if(file_exists($src)){
            $ext = pathinfo($src, PATHINFO_EXTENSION);
            $data = base64_encode(file_get_contents($src));
            $src = 'data:image/'.$ext.';base64,'.$data;
        }
        $body = '<div><img src="'.$src.'" style="max-width:200px;height:auto;margin-left:15px;display:block;"></div><br>'.$body;
    }
    $mail->Body = $body;
    foreach ($recipientList as $addr) {
        $mail->addAddress($addr);
    }
    $ok = $mail->send();
    if(!$ok){
        $error = $mail->ErrorInfo ?: 'Bilinmeyen hata';
    }
    $logContent = $body;
    if(!$ok && $error){
        $logContent .= '<hr><strong>Hata:</strong> '.htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    $log = $pdo->prepare('INSERT INTO email_logs(service_id,client_id,email_to,subject,content,sent_at) VALUES (?,?,?,?,?,NOW())');
    $log->execute([$serviceId,$clientId,implode(',', $recipientList),$subject,$logContent]);
    return $ok;
}
?>

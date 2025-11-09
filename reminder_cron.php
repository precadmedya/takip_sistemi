<?php
require __DIR__.'/includes/db.php';
require __DIR__.'/includes/functions.php';
$date = date('Y-m-d');
$stmt=$pdo->query("SELECT s.id,s.due_date,s.reminder_days,c.id AS cid,c.full_name,c.email,s.site_name FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.reminder_enabled=1");
$usdRate = getUsdRate($pdo);
$settings = getEmailSettings($pdo);
$copyEmail = trim($settings['reminder_copy_email'] ?? 'info@precadmedya.com.tr');
$itemsStmt = $pdo->prepare('SELECT * FROM service_items WHERE service_id=?');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $svc){
    $days=(strtotime($svc['due_date'])-strtotime($date))/86400;
    $sel=array_filter(array_map('intval',explode(',',$svc['reminder_days'])));
    if(in_array($days,$sel)){
        $err='';
        $subject='Ödeme Hatırlatma';
        $itemsStmt->execute([$svc['id']]);
        $rows='';
        $totalTl=0; $vatTl=0; $totalUsd=0; $vatUsd=0;
        foreach($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $it){
            $sub=$it['unit_price']*$it['quantity'];
            $vat=$sub*$it['vat_rate']/100;
            $lineTotal=$sub+$vat;
            $isUsd=$it['currency']==='USD';
            $subTl=$isUsd?$sub*$usdRate:$sub;
            $vatTlLine=$isUsd?$vat*$usdRate:$vat;
            $lineTl=$isUsd?$lineTotal*$usdRate:$lineTotal;
            $totalTl += $subTl;
            $vatTl += $vatTlLine;
            if($usdRate>0){
                $subUsd = $isUsd ? $sub : $sub/$usdRate;
                $vatUsdLine = $isUsd ? $vat : $vat/$usdRate;
                $lineUsd = $subUsd + $vatUsdLine;
                $totalUsd += $subUsd;
                $vatUsd += $vatUsdLine;
            } else {
                $lineUsd = 0;
            }
            $currencySymbol = $isUsd ? '$' : '₺';
            $rows.='<tr><td>'.htmlspecialchars($it['item_name']).'</td>';
            $rows.='<td>'.$it['quantity'].' '.htmlspecialchars($it['unit']).'</td>';
            $rows.='<td>'.number_format($it['unit_price'],2,',','.').' '.$currencySymbol.'</td>';
            $rows.='<td>'.$it['vat_rate'].'%</td>';
            $rows.='<td>'.number_format($vatTlLine,2,',','.').' ₺';
            if($usdRate>0){
                $rows.=' ('.number_format($isUsd ? $vat : $vatUsdLine,2,',','.').' $)';
            }
            $rows.='</td>';
            $rows.='<td>'.number_format($lineTl,2,',','.').' ₺';
            if($usdRate>0){
                $rows.=' ('.number_format($lineUsd,2,',','.').' $)';
            }
            $rows.='</td>';
            $rows.='<td>'.htmlspecialchars($svc['site_name']).'</td></tr>';
        }
        $grandTl=$totalTl+$vatTl;
        $grandUsd=$usdRate>0 ? $totalUsd+$vatUsd : 0;
        $body = "Sayın {$svc['full_name']},<br><br>{$svc['site_name']} hizmetinizin bitiş tarihi yaklaşmaktadır. Bitiş tarihi: ".date('d.m.Y',strtotime($svc['due_date'])).".<br><br>";
        if($rows){
            $body .= '<table border="1" cellpadding="5" style="border-collapse:collapse">';
            $body .= '<tr><th>Hizmet</th><th>Süre</th><th>Birim Fiyat</th><th>KDV Oranı</th><th>KDV Tutarı (TL / USD)</th><th>Toplam (TL / USD)</th><th>Site</th></tr>'.$rows.'</table>';
            $body .= '<br><strong>Birim Fiyatı:</strong> '.number_format($totalTl,2,',','.').' ₺';
            if($usdRate>0){
                $body .= ' ('.number_format($totalUsd,2,',','.').' $)';
            }
            $body .= '<br><strong>KDV Tutarı:</strong> '.number_format($vatTl,2,',','.').' ₺';
            if($usdRate>0){
                $body .= ' ('.number_format($vatUsd,2,',','.').' $)';
            }
            $body .= '<br><strong>Genel Toplam:</strong> '.number_format($grandTl,2,',','.').' ₺';
            if($usdRate>0){
                $body .= ' ('.number_format($grandUsd,2,',','.').' $)';
            }
            $body .= '<br><br>';
        }
        $body .= 'Hizmet süresini uzatmak veya ödeme yapmak için lütfen bizimle iletişime geçin.<br><br>Saygılarımızla,<br>'.$settings['from_name'];
        $recipients = [];
        foreach ([$svc['email'], $copyEmail] as $addr) {
            $addr = trim((string)$addr);
            if ($addr !== '') {
                $recipients[] = $addr;
            }
        }
        $recipients = array_values(array_unique($recipients));
        sendMail($pdo,$recipients,$subject,$body,$err,$svc['id'],$svc['cid']);
    }
}

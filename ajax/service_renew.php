<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$usdRate = getUsdRate($pdo);
$usdRate = $usdRate ?: 1;

function fetchService(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT s.*, c.full_name FROM services s JOIN customers c ON s.customer_id=c.id WHERE s.id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function calculateDebt(PDO $pdo, array $service, float $usdRate): array {
    $paidStmt = $pdo->prepare('SELECT SUM(amount_try) FROM payments WHERE service_id=?');
    $paidStmt->execute([$service['id']]);
    $paidTry = (float) $paidStmt->fetchColumn();
    $serviceTotalTry = (float) $service['price_try'] * (1 + ((float) $service['vat_rate']) / 100);
    $remainTry = $serviceTotalTry - $paidTry;
    if ($remainTry < 0) {
        $remainTry = 0;
    }
    $remainCur = $service['currency'] === 'USD' ? ($remainTry / $usdRate) : $remainTry;

    return [
        'paid_try' => $paidTry,
        'total_try' => $serviceTotalTry,
        'remain_try' => $remainTry,
        'remain_cur' => $remainCur,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $serviceId = (int) ($_GET['id'] ?? 0);
    if (!$serviceId) {
        http_response_code(400);
        echo json_encode(['error' => 'Geçersiz hizmet.']);
        exit;
    }

    $service = fetchService($pdo, $serviceId);
    if (!$service) {
        http_response_code(404);
        echo json_encode(['error' => 'Hizmet bulunamadı.']);
        exit;
    }

    $debt = calculateDebt($pdo, $service, $usdRate);
    $vatRate = (float) $service['vat_rate'];
    $price = (float) $service['price'];
    $priceWithVat = $price * (1 + $vatRate / 100);

    echo json_encode([
        'service' => [
            'id' => (int) $service['id'],
            'customer' => $service['full_name'],
            'service_type' => $service['service_type'],
            'site_name' => $service['site_name'],
            'due_date' => $service['due_date'],
            'due_date_formatted' => $service['due_date'] ? date('d.m.Y', strtotime($service['due_date'])) : '',
            'price' => $price,
            'currency' => $service['currency'],
            'vat_rate' => $vatRate,
            'price_with_vat' => $priceWithVat,
            'price_display' => number_format($price, 2, ',', '.') . ' ' . $service['currency'],
            'price_with_vat_display' => number_format($priceWithVat, 2, ',', '.') . ' ' . $service['currency'],
            'remaining_display' => number_format($debt['remain_cur'], 2, ',', '.') . ' ' . $service['currency'] . ' (' . number_format($debt['remain_try'], 2, ',', '.') . ' TL)',
            'remaining_try' => $debt['remain_try'],
            'remaining_cur' => $debt['remain_cur'],
        ],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $years = max(1, (int) ($_POST['years'] ?? 1));

    if (!$serviceId) {
        http_response_code(400);
        echo json_encode(['error' => 'Geçersiz hizmet.']);
        exit;
    }

    $service = fetchService($pdo, $serviceId);
    if (!$service) {
        http_response_code(404);
        echo json_encode(['error' => 'Hizmet bulunamadı.']);
        exit;
    }

    $debt = calculateDebt($pdo, $service, $usdRate);

    $pdo->beginTransaction();
    try {
        if ($debt['remain_try'] > 0) {
            $amountOrig = $service['currency'] === 'USD' ? ($debt['remain_try'] / $usdRate) : $debt['remain_try'];
            $payStmt = $pdo->prepare('INSERT INTO payments (customer_id, service_id, amount_try, amount_orig, currency) VALUES (?,?,?,?,?)');
            $payStmt->execute([
                $service['customer_id'],
                $service['id'],
                $debt['remain_try'],
                $amountOrig,
                $service['currency'],
            ]);
        }

        $start = $service['due_date'] ?: date('Y-m-d');
        $due = date('Y-m-d', strtotime($start . ' +' . $years . ' year'));
        $duration = (int) ((strtotime($due) - strtotime($start)) / 86400);

        $renewPrice = (float) $service['price'] * $years;
        $priceTry = $service['currency'] === 'USD' ? $renewPrice * $usdRate : $renewPrice;

        $insert = $pdo->prepare("INSERT INTO services (customer_id, product_id, provider_id, site_name, service_type, start_date, due_date, duration, unit, price, currency, vat_rate, price_try, status, notes, reminder_enabled, reminder_days, created_at) VALUES (?,?,?,?,?,?,?,?, 'gün', ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $insert->execute([
            $service['customer_id'],
            $service['product_id'],
            $service['provider_id'],
            $service['site_name'],
            $service['service_type'],
            $start,
            $due,
            $duration,
            $renewPrice,
            $service['currency'],
            $service['vat_rate'],
            $priceTry,
            $service['status'],
            $service['notes'],
            $service['reminder_enabled'],
            $service['reminder_days'],
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'İşlem sırasında hata oluştu.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Desteklenmeyen istek.']);

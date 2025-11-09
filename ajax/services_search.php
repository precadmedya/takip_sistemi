<?php
require __DIR__.'/../includes/auth.php';
require __DIR__.'/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit < 1 || $limit > 500) {
    $limit = 200;
}

$params = [];
$where = '';
if ($q !== '') {
    $like = '%'.$q.'%';
    $params[':search'] = $like;
    $where = "WHERE s.service_type LIKE :search OR s.site_name LIKE :search OR s.status LIKE :search OR s.notes LIKE :search OR c.full_name LIKE :search OR CAST(s.id AS CHAR) LIKE :search";
}

$sql = "SELECT s.*, c.full_name
        FROM services s
        JOIN customers c ON s.customer_id = c.id
        $where
        ORDER BY s.id DESC
        LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $total = (float)$row['price_try'] * (1 + ((float)$row['vat_rate'])/100);
    $services[] = [
        'id' => (int)$row['id'],
        'full_name' => $row['full_name'],
        'service_type' => $row['service_type'],
        'site_name' => $row['site_name'],
        'start_date' => $row['start_date'],
        'start_date_formatted' => $row['start_date'] ? date('d.m.Y', strtotime($row['start_date'])) : '',
        'due_date' => $row['due_date'],
        'due_date_formatted' => $row['due_date'] ? date('d.m.Y', strtotime($row['due_date'])) : '',
        'price' => (float)$row['price'],
        'currency' => $row['currency'],
        'price_display' => number_format((float)$row['price'], 2, ',', '.').' '.$row['currency'],
        'price_try' => (float)$row['price_try'],
        'price_try_display' => number_format((float)$row['price_try'], 2, ',', '.').' ₺',
        'vat_rate' => (float)$row['vat_rate'],
        'total_display' => number_format($total, 2, ',', '.').' ₺',
        'status' => $row['status'],
        'notes' => $row['notes'],
        'created_at' => $row['created_at'],
        'created_at_formatted' => $row['created_at'] ? date('d.m.Y', strtotime($row['created_at'])) : '',
    ];
}

echo json_encode([
    'services' => $services,
]);

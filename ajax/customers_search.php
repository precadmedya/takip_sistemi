<?php
require __DIR__.'/../includes/auth.php';
require __DIR__.'/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$validSort = ['company','balance','full_name','email','phone','created_at','id'];
$sort = $_GET['sort'] ?? 'company';
if (!in_array($sort, $validSort, true)) {
    $sort = 'company';
}
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

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
    $where = "WHERE c.full_name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search OR c.company LIKE :search OR c.address LIKE :search OR CAST(c.id AS CHAR) LIKE :search";
}

$sql = "SELECT c.*, IFNULL(SUM(s.price_try * (1 + s.vat_rate/100)),0) - IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.customer_id=c.id),0) AS balance
        FROM customers c
        LEFT JOIN services s ON s.customer_id = c.id
        $where
        GROUP BY c.id
        ORDER BY $sort $dir
        LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $customers[] = [
        'id' => (int)$row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'company' => $row['company'],
        'address' => $row['address'],
        'balance' => (float)$row['balance'],
        'balance_formatted' => number_format((float)$row['balance'], 2, ',', '.').' ₺',
        'created_at' => $row['created_at'],
        'created_at_formatted' => $row['created_at'] ? date('d.m.Y', strtotime($row['created_at'])) : '',
    ];
}

echo json_encode([
    'customers' => $customers,
]);

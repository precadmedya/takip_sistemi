<?php
require __DIR__.'/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$customer){
    header('Location: /customers.php');
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt = $pdo->prepare('UPDATE customers SET full_name=?, email=?, phone=?, company=?, address=? WHERE id=?');
    $stmt->execute([
        $_POST['full_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['company'],
        $_POST['address'],
        $id
    ]);
    header('Location: /customers.php');
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Müşteri Düzenle</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Ad Soyad</label>
    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($customer['full_name']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">E-Posta</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Telefon</label>
    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Şirket</label>
    <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($customer['company']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Adres</label>
    <textarea name="address" class="form-control"><?= htmlspecialchars($customer['address']) ?></textarea>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
  <a href="/customers.php" class="btn btn-secondary">İptal</a>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO customers (full_name, email, phone, company, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['company'],
        $_POST['address']
    ]);
    header('Location: customers.php');
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Müşteri Ekle</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Ad Soyad</label>
    <input type="text" name="full_name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">E-Posta</label>
    <input type="email" name="email" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Telefon</label>
    <input type="text" name="phone" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Şirket</label>
    <input type="text" name="company" class="form-control">
  </div>
  <div class="mb-3">
    <label class="form-label">Adres</label>
    <textarea name="address" class="form-control"></textarea>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

<?php
require __DIR__.'/includes/auth.php';
$customers = $pdo->query("SELECT id, full_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO services (customer_id, service_type, start_date, duration, unit, price, currency, vat_rate, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['customer_id'],
        $_POST['service_type'],
        $_POST['start_date'],
        $_POST['duration'],
        $_POST['unit'],
        $_POST['price'],
        $_POST['currency'],
        $_POST['vat_rate'],
        $_POST['status'],
        $_POST['notes']
    ]);
    header('Location: /services.php');
    exit;
}

include __DIR__.'/includes/header.php';
?>
<h1>Hizmet Ekle</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Müşteri</label>
    <select name="customer_id" class="form-control" required>
      <?php foreach ($customers as $c): ?>
      <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Hizmet Tipi</label>
    <input type="text" name="service_type" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Başlangıç Tarihi</label>
    <input type="date" name="start_date" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Süre</label>
    <input type="number" name="duration" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Birim</label>
    <select name="unit" class="form-control">
      <option value="yıl">Yıl</option>
      <option value="ay">Ay</option>
      <option value="adet">Adet</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Fiyat</label>
    <input type="text" name="price" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Döviz</label>
    <select name="currency" class="form-control">
      <option value="TRY">TRY</option>
      <option value="USD">USD</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">KDV Oranı</label>
    <input type="text" name="vat_rate" class="form-control" value="18">
  </div>
  <div class="mb-3">
    <label class="form-label">Durum</label>
    <select name="status" class="form-control">
      <option value="aktif">Aktif</option>
      <option value="pasif">Pasif</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Not</label>
    <textarea name="notes" class="form-control"></textarea>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php include __DIR__.'/includes/footer.php'; ?>

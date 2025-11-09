<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/functions.php';
include __DIR__.'/includes/header.php';

$stmt = $pdo->query("SELECT s.*, c.full_name FROM services s JOIN customers c ON s.customer_id=c.id ORDER BY s.id DESC");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$usdRate = getUsdRate($pdo);
?>
<h1>Hizmetler</h1>
<a href="service_add.php" class="btn btn-primary mb-3">Hizmet Ekle</a>
<div class="mb-3">
  <input type="text" id="service-search" class="form-control" placeholder="Hizmet, müşteri veya alan adı ara" autocomplete="off">
</div>
<table class="table table-bordered" id="services-table">
  <thead>
    <tr>
       <th>ID</th>
       <th>Müşteri</th>
       <th>Hizmet Türü</th>
       <th>Site</th>
       <th>Başlangıç</th>
       <th>Ödeme Tarihi</th>
       <th>Fiyat</th>
       <th>Fiyat TL</th>
       <th>KDV</th>
       <th>Genel Toplam</th>
       <th>Durum</th>
       <th>Not</th>
       <th>Oluşturma</th>
       <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($services as $s): ?>
    <tr>
      <td><?= $s['id'] ?></td>
      <td><?= htmlspecialchars($s['full_name']) ?></td>
      <td><?= htmlspecialchars($s['service_type']) ?></td>
      <td><?= htmlspecialchars($s['site_name']) ?></td>
      <td><?= date('d.m.Y', strtotime($s['start_date'])) ?></td>
      <td><?= date('d.m.Y', strtotime($s['due_date'])) ?></td>
      <td><?= number_format($s['price'],2,',','.') . ' ' . $s['currency'] ?></td>
      <td><?= number_format($s['price_try'],2,',','.') ?> ₺</td>
      <td><?= $s['vat_rate'] ?>%</td>
      <td><?= number_format($s['price_try'] * (1 + $s['vat_rate']/100), 2, ',', '.') ?> ₺</td>
      <td><?= htmlspecialchars($s['status']) ?></td>
      <td><?= htmlspecialchars($s['notes']) ?></td>
      <td><?= date('d.m.Y', strtotime($s['created_at'])) ?></td>
      <td>
        <a href="service.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">Detay</a>
        <a href="service_payment.php?service_id=<?= $s['id'] ?>" class="btn btn-sm btn-primary">Tahsilat</a>
        <a href="service_edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="service_delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
const serviceSearchInput = document.getElementById('service-search');
const serviceTableBody = document.querySelector('#services-table tbody');
let serviceTimer;

async function fetchServices() {
  const params = new URLSearchParams();
  if (serviceSearchInput.value.trim() !== '') {
    params.set('q', serviceSearchInput.value.trim());
  }
  try {
    const response = await fetch('ajax/services_search.php?' + params.toString(), {
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    });
    if (!response.ok) {
      throw new Error('Sunucu hatası');
    }
    const data = await response.json();
    renderServiceRows(data.services);
  } catch (error) {
    serviceTableBody.innerHTML = '<tr><td colspan="14" class="text-danger">Hizmet listesi alınamadı.</td></tr>';
  }
}

function renderServiceRows(services) {
  if (!services.length) {
    serviceTableBody.innerHTML = '<tr><td colspan="14">Sonuç bulunamadı.</td></tr>';
    return;
  }
  const rows = services.map(service => {
    const start = service.start_date_formatted || '';
    const due = service.due_date_formatted || '';
    const created = service.created_at_formatted || '';
    return `<tr>
      <td>${service.id}</td>
      <td>${escapeHtml(service.full_name || '')}</td>
      <td>${escapeHtml(service.service_type || '')}</td>
      <td>${escapeHtml(service.site_name || '')}</td>
      <td>${start}</td>
      <td>${due}</td>
      <td>${service.price_display}</td>
      <td>${service.price_try_display}</td>
      <td>${service.vat_rate}%</td>
      <td>${service.total_display}</td>
      <td>${escapeHtml(service.status || '')}</td>
      <td>${escapeHtml(service.notes || '')}</td>
      <td>${created}</td>
      <td>
        <a href="service.php?id=${service.id}" class="btn btn-sm btn-info">Detay</a>
        <a href="service_payment.php?service_id=${service.id}" class="btn btn-sm btn-primary">Tahsilat</a>
        <a href="service_edit.php?id=${service.id}" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="service_delete.php?id=${service.id}" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
      </td>
    </tr>`;
  }).join('');
  serviceTableBody.innerHTML = rows;
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

serviceSearchInput.addEventListener('input', () => {
  clearTimeout(serviceTimer);
  serviceTimer = setTimeout(fetchServices, 250);
});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

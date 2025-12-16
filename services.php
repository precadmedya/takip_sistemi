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
        <button type="button" class="btn btn-sm btn-success renew-btn" data-service-id="<?= $s['id'] ?>" title="Hizmeti Yenile">↻</button>
        <a href="service_edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="service_delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="modal fade" id="renewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Hizmeti Yenile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body">
        <div id="renewAlert" class="alert alert-danger d-none"></div>
        <p class="mb-1"><strong>Müşteri:</strong> <span id="renewCustomer"></span></p>
        <p class="mb-1"><strong>Hizmet:</strong> <span id="renewService"></span></p>
        <p class="mb-1"><strong>Mevcut Son Ödeme:</strong> <span id="renewDue"></span></p>
        <p class="mb-3"><strong>Yıllık Tutar:</strong> <span id="renewPrice"></span></p>
        <form id="renewForm">
          <input type="hidden" name="service_id" id="renewServiceId">
          <div class="mb-3">
            <label class="form-label" for="renewYears">Kaç Yıl Yenilensin?</label>
            <select id="renewYears" name="years" class="form-control">
              <option value="1">1 Yıl</option>
              <option value="2">2 Yıl</option>
              <option value="3">3 Yıl</option>
              <option value="4">4 Yıl</option>
              <option value="5">5 Yıl</option>
            </select>
          </div>
          <div class="alert alert-secondary">
            <div><strong>Eski Borç Tahsilatı:</strong> <span id="renewDebt"></span></div>
            <div><strong>Yenileme (KDV dahil):</strong> <span id="renewTotal"></span></div>
            <div><strong>Yeni Son Ödeme Tarihi:</strong> <span id="renewNewDue"></span></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        <button type="submit" form="renewForm" class="btn btn-success">Tahsil Et ve Yenile</button>
      </div>
    </div>
  </div>
</div>
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
        <button type="button" class="btn btn-sm btn-success renew-btn" data-service-id="${service.id}" title="Hizmeti Yenile">↻</button>
        <a href="service_edit.php?id=${service.id}" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="service_delete.php?id=${service.id}" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
      </td>
    </tr>`;
  }).join('');
  serviceTableBody.innerHTML = rows;
}

const renewModalEl = document.getElementById('renewModal');
const renewModal = new bootstrap.Modal(renewModalEl);
const renewAlert = document.getElementById('renewAlert');
const renewForm = document.getElementById('renewForm');
const renewYears = document.getElementById('renewYears');
let renewData = null;

function resetRenewModal() {
  renewAlert.classList.add('d-none');
  renewAlert.textContent = '';
  renewData = null;
  renewForm.reset();
  document.getElementById('renewCustomer').textContent = '';
  document.getElementById('renewService').textContent = '';
  document.getElementById('renewDue').textContent = '';
  document.getElementById('renewPrice').textContent = '';
  document.getElementById('renewDebt').textContent = '';
  document.getElementById('renewTotal').textContent = '';
  document.getElementById('renewNewDue').textContent = '';
}

function formatDate(date) {
  const d = new Date(date);
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleDateString('tr-TR');
}

function updateRenewSummary() {
  if (!renewData) return;
  const years = parseInt(renewYears.value, 10) || 1;
  const base = renewData.price * years;
  const withVat = base * (1 + (renewData.vat_rate || 0) / 100);
  const startDate = renewData.due_date ? new Date(renewData.due_date + 'T00:00:00') : new Date();
  startDate.setFullYear(startDate.getFullYear() + years);

  document.getElementById('renewTotal').textContent = `${withVat.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2})} ${renewData.currency}`;
  document.getElementById('renewNewDue').textContent = formatDate(startDate);
}

async function openRenewModal(serviceId) {
  resetRenewModal();
  document.getElementById('renewServiceId').value = serviceId;
  try {
    const response = await fetch('ajax/service_renew.php?id=' + serviceId, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
    if (!response.ok) {
      throw new Error('Bilgiler alınamadı');
    }
    const data = await response.json();
    renewData = data.service;
    document.getElementById('renewCustomer').textContent = data.service.customer;
    document.getElementById('renewService').textContent = `${data.service.service_type} / ${data.service.site_name}`;
    document.getElementById('renewDue').textContent = data.service.due_date_formatted;
    document.getElementById('renewPrice').textContent = `${data.service.price_with_vat_display} (KDV dahil)`;
    document.getElementById('renewDebt').textContent = data.service.remaining_display;
    updateRenewSummary();
    renewModal.show();
  } catch (error) {
    alert('Yenileme bilgileri alınamadı.');
  }
}

document.body.addEventListener('click', (event) => {
  const target = event.target.closest('.renew-btn');
  if (target) {
    openRenewModal(target.dataset.serviceId);
  }
});

renewYears.addEventListener('change', updateRenewSummary);

renewForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  renewAlert.classList.add('d-none');
  renewAlert.textContent = '';
  const formData = new FormData(renewForm);
  try {
    const response = await fetch('ajax/service_renew.php', {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: formData
    });
    const data = await response.json();
    if (!response.ok || data.error) {
      throw new Error(data.error || 'İşlem başarısız.');
    }
    renewModal.hide();
    location.reload();
  } catch (error) {
    renewAlert.textContent = error.message || 'İşlem sırasında hata oluştu.';
    renewAlert.classList.remove('d-none');
  }
});

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

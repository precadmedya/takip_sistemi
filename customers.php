<?php
require __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

// CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=customers.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','full_name','email','phone','company','address']);
    $stmt = $pdo->query('SELECT id, full_name, email, phone, company, address FROM customers ORDER BY id');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// CSV import
if (isset($_POST['import']) && isset($_FILES['import_file']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
    $file = fopen($_FILES['import_file']['tmp_name'], 'r');
    $header = fgetcsv($file);
    $count = 0;
    if ($header) {
        while (($data = fgetcsv($file)) !== false) {
            $row = array_combine($header, $data);
            if (!$row) continue;
            $email = trim($row['email'] ?? '');
            $id = $row['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT id FROM customers WHERE id=?');
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE customers SET full_name=?, email=?, phone=?, company=?, address=? WHERE id=?');
                    $u->execute([$row['full_name'],$email,$row['phone'],$row['company'],$row['address'],$id]);
                    $count++; continue;
                }
            }
            if ($email) {
                $stmt = $pdo->prepare('SELECT id FROM customers WHERE email=?');
                $stmt->execute([$email]);
                if ($cid = $stmt->fetchColumn()) {
                    $u = $pdo->prepare('UPDATE customers SET full_name=?, phone=?, company=?, address=? WHERE id=?');
                    $u->execute([$row['full_name'],$row['phone'],$row['company'],$row['address'],$cid]);
                    $count++; continue;
                }
            }
            $i = $pdo->prepare('INSERT INTO customers(full_name,email,phone,company,address) VALUES (?,?,?,?,?)');
            $i->execute([$row['full_name'],$email,$row['phone'],$row['company'],$row['address']]);
            $count++;
        }
    }
    fclose($file);
    $_SESSION['message'] = $count.' kayıt içeri aktarıldı';
    header('Location: customers.php');
    exit;
}

include __DIR__.'/includes/header.php';

$validSort = ['company','balance','full_name','email','phone','created_at','id'];
$sort = $_GET['sort'] ?? 'company';
if (!in_array($sort, $validSort)) {
    $sort = 'company';
}
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$stmt = $pdo->prepare("SELECT c.*,
    IFNULL(SUM(s.price_try * (1 + s.vat_rate/100)),0) -
    IFNULL((SELECT SUM(amount_try) FROM payments p WHERE p.customer_id=c.id),0) AS balance
    FROM customers c
    LEFT JOIN services s ON s.customer_id = c.id
    GROUP BY c.id
    ORDER BY $sort $dir");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Müşteriler</h1>
<a href="customer_add.php" class="btn btn-primary mb-3">Müşteri Ekle</a>
<a href="customers.php?export=1" class="btn btn-secondary mb-3 ms-2">CSV Dışa Aktar</a>
<form method="post" enctype="multipart/form-data" class="d-inline-block mb-3 ms-2">
  <input type="file" name="import_file" accept=".csv" required class="form-control d-inline-block" style="width:auto;">
  <button type="submit" name="import" class="btn btn-secondary">CSV İçe Aktar</button>
</form>
<div class="mb-3 mt-3">
  <input type="text" id="customer-search" class="form-control" placeholder="Müşteri, e-posta, telefon veya şirket ara" autocomplete="off" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
</div>
<table class="table table-bordered" id="customers-table">
  <thead>
    <?php
      function sortLink(string $key, string $label, string $currentSort, string $currentDir): string {
          $nextDir = ($currentSort === $key && $currentDir === 'ASC') ? 'desc' : 'asc';
          $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
          $keyEsc = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
          $dirEsc = htmlspecialchars($nextDir, ENT_QUOTES, 'UTF-8');
          return sprintf(
              '<a href="?sort=%1$s&dir=%2$s" data-sort="%1$s" data-next-dir="%2$s" data-label="%3$s">%3$s</a>',
              $keyEsc,
              $dirEsc,
              $labelEsc
          );
      }
    ?>
    <tr>
       <th><?= sortLink('id','ID',$sort,$dir) ?></th>
       <th><?= sortLink('full_name','Ad Soyad',$sort,$dir) ?></th>
       <th><?= sortLink('email','E-Posta',$sort,$dir) ?></th>
       <th><?= sortLink('phone','Telefon',$sort,$dir) ?></th>
       <th><?= sortLink('company','Şirket',$sort,$dir) ?></th>
       <th><?= sortLink('address','Adres',$sort,$dir) ?></th>
       <th><?= sortLink('balance','Bakiye (TL)',$sort,$dir) ?></th>
       <th><?= sortLink('created_at','Oluşturma',$sort,$dir) ?></th>
       <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td><?= htmlspecialchars($c['id']) ?></td>
      <td><?= htmlspecialchars($c['full_name']) ?></td>
      <td><?= htmlspecialchars($c['email']) ?></td>
      <td><?= htmlspecialchars($c['phone']) ?></td>
      <td><?= htmlspecialchars($c['company']) ?></td>
      <td><?= htmlspecialchars($c['address']) ?></td>
      <td><?= number_format($c['balance'], 2, ',', '.') ?> ₺</td>
      <td><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
      <td>
        <a href="customer.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info">Detay</a>
        <a href="customer_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
const customerSearchInput = document.getElementById('customer-search');
const customerTableBody = document.querySelector('#customers-table tbody');
const customerSortAnchors = Array.from(document.querySelectorAll('#customers-table thead a'));
let customerSort = <?= json_encode($sort) ?>;
let customerDir = <?= json_encode(strtolower($dir)) ?>;
let customerTimer;

customerSortAnchors.forEach(anchor => {
  if (!anchor.dataset.label) {
    anchor.dataset.label = anchor.textContent.trim();
  }
});

async function fetchCustomers() {
  const params = new URLSearchParams();
  const query = customerSearchInput.value.trim();
  if (query !== '') {
    params.set('q', query);
  }
  params.set('sort', customerSort);
  params.set('dir', customerDir);
  try {
    const response = await fetch('ajax/customers_search.php?' + params.toString(), {
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    });
    if (!response.ok) {
      throw new Error('Sunucu hatası');
    }
    const data = await response.json();
    renderCustomerRows(data.customers);
  } catch (error) {
    customerTableBody.innerHTML = '<tr><td colspan="9" class="text-danger">Müşteri listesi alınamadı.</td></tr>';
  } finally {
    syncQueryString();
    updateSortIndicators();
  }
}

function renderCustomerRows(customers) {
  if (!customers.length) {
    customerTableBody.innerHTML = '<tr><td colspan="9">Sonuç bulunamadı.</td></tr>';
    return;
  }
  const rows = customers.map(customer => {
    const created = customer.created_at_formatted || '';
    return `<tr>
      <td>${customer.id}</td>
      <td>${escapeHtml(customer.full_name || '')}</td>
      <td>${escapeHtml(customer.email || '')}</td>
      <td>${escapeHtml(customer.phone || '')}</td>
      <td>${escapeHtml(customer.company || '')}</td>
      <td>${escapeHtml(customer.address || '')}</td>
      <td>${customer.balance_formatted}</td>
      <td>${created}</td>
      <td>
        <a href="customer.php?id=${customer.id}" class="btn btn-sm btn-info">Detay</a>
        <a href="customer_delete.php?id=${customer.id}" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?');">Sil</a>
      </td>
    </tr>`;
  }).join('');
  customerTableBody.innerHTML = rows;
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

function syncQueryString() {
  const url = new URL(window.location.href);
  const query = customerSearchInput.value.trim();
  if (query !== '') {
    url.searchParams.set('q', query);
  } else {
    url.searchParams.delete('q');
  }
  url.searchParams.set('sort', customerSort);
  url.searchParams.set('dir', customerDir);
  const queryString = url.searchParams.toString();
  const newUrl = url.pathname + (queryString ? '?' + queryString : '');
  window.history.replaceState({}, '', newUrl);
}

function updateSortIndicators() {
  customerSortAnchors.forEach(anchor => {
    const sortKey = anchor.dataset.sort;
    if (!sortKey) {
      return;
    }
    const isActive = sortKey === customerSort;
    const nextDir = isActive && customerDir === 'asc' ? 'desc' : 'asc';
    anchor.dataset.nextDir = nextDir;
    const label = anchor.dataset.label || anchor.textContent.trim();
    anchor.textContent = isActive ? `${label} ${customerDir === 'asc' ? '↑' : '↓'}` : label;
    const th = anchor.closest('th');
    if (th) {
      th.setAttribute('aria-sort', isActive ? (customerDir === 'asc' ? 'ascending' : 'descending') : 'none');
    }
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortKey);
    url.searchParams.set('dir', nextDir);
    const query = customerSearchInput.value.trim();
    if (query !== '') {
      url.searchParams.set('q', query);
    } else {
      url.searchParams.delete('q');
    }
    const queryString = url.searchParams.toString();
    anchor.href = url.pathname + (queryString ? '?' + queryString : '');
  });
}

customerSearchInput.addEventListener('input', () => {
  clearTimeout(customerTimer);
  customerTimer = setTimeout(fetchCustomers, 250);
});

customerSortAnchors.forEach(anchor => {
  anchor.addEventListener('click', event => {
    event.preventDefault();
    const sortKey = anchor.dataset.sort;
    if (!sortKey) {
      return;
    }
    if (customerSort === sortKey) {
      customerDir = customerDir === 'asc' ? 'desc' : 'asc';
    } else {
      customerSort = sortKey;
      customerDir = 'asc';
    }
    updateSortIndicators();
    fetchCustomers();
  });
});

updateSortIndicators();
if (customerSearchInput.value.trim() !== '') {
  fetchCustomers();
}
</script>
<?php include __DIR__.'/includes/footer.php'; ?>

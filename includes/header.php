<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Takip Sistemi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php
$settings = [];
foreach (['logo','logo_header_width','logo_header_height'] as $k) {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->execute([$k]);
    $settings[$k] = $stmt->fetchColumn() ?: '';
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
 <div class="container-fluid">
  <a class="navbar-brand" href="dashboard.php">
    <?php if ($settings['logo']): ?>
      <img src="<?= htmlspecialchars($settings['logo']) ?>" alt="Logo" style="width:<?= (int)$settings['logo_header_width'] ?>px;height:<?= (int)$settings['logo_header_height'] ?>px;object-fit:contain;">
    <?php else: ?>Takip Sistemi<?php endif; ?>
  </a>
  <div class="collapse navbar-collapse">
   <ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <li class="nav-item"><a class="nav-link" href="dashboard.php">Anasayfa</a></li>
    <li class="nav-item"><a class="nav-link" href="customers.php">Müşteriler</a></li>
    <li class="nav-item"><a class="nav-link" href="services.php">Hizmetler</a></li>
    <li class="nav-item"><a class="nav-link" href="products.php">Ürünler</a></li>
    <li class="nav-item"><a class="nav-link" href="providers.php">Sağlayıcılar</a></li>
    <li class="nav-item"><a class="nav-link" href="users.php">Kullanıcılar</a></li>
    <li class="nav-item"><a class="nav-link" href="exchange_rates.php">Kur Bilgisi</a></li>
    <li class="nav-item"><a class="nav-link" href="settings.php">Ayarlar</a></li>
   </ul>
   <a href="logout.php" class="btn btn-outline-secondary">Çıkış</a>
  </div>
 </div>
</nav>
<div class="container mt-4">

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Takip Sistemi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php
require_once __DIR__.'/functions.php';
$settings = [];
foreach (['logo','logo_header_width','logo_header_height'] as $k) {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->execute([$k]);
    $settings[$k] = $stmt->fetchColumn() ?: '';
}
$currentRate = getUsdRate($pdo);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
 <div class="container-fluid">
  <a class="navbar-brand me-3" href="dashboard.php">
    <?php if ($settings['logo']): ?>
      <img src="<?= htmlspecialchars($settings['logo']) ?>" alt="Logo" style="width:<?= (int)$settings['logo_header_width'] ?>px;height:<?= (int)$settings['logo_header_height'] ?>px;object-fit:contain;">
    <?php else: ?>Takip Sistemi<?php endif; ?>
  </a>
  <div class="collapse navbar-collapse justify-content-center">
   <ul class="navbar-nav mb-2 mb-lg-0">
    <li class="nav-item"><a class="nav-link" href="dashboard.php">Anasayfa</a></li>
    <li class="nav-item"><a class="nav-link" href="customers.php">Müşteriler</a></li>
    <li class="nav-item"><a class="nav-link" href="services.php">Hizmetler</a></li>
    <li class="nav-item"><a class="nav-link" href="products.php">Ürünler</a></li>
    <li class="nav-item"><a class="nav-link" href="providers.php">Sağlayıcılar</a></li>
    <li class="nav-item"><a class="nav-link" href="purchase_add.php">Ürün Satın Al</a></li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Ayarlar</a>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="settings.php">Genel Ayarlar</a></li>
        <li><a class="dropdown-item" href="users.php">Kullanıcılar</a></li>
      </ul>
    </li>
   </ul>
   <span class="navbar-text me-3">Kur: <?= number_format($currentRate,4,',','.') ?></span>
   <a href="update_rates.php" class="btn btn-warning btn-sm me-3">Kur Güncelle</a>
   <a href="logout.php" class="btn btn-outline-secondary">Çıkış</a>
  </div>
</div>
</nav>
<div class="container mt-4">
<?php if(!empty($_SESSION['message'])): ?>
  <div class="alert alert-success">
    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
  </div>
<?php endif; ?>

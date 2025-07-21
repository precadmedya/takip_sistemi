<?php
require __DIR__ . '/includes/db.php';
session_start();

if (isset($_SESSION['user_id']) && (time() - ($_SESSION['last_active'] ?? 0) < 1800)) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['last_active'] = time();
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = 'Giriş Bilgileri Hatalı';
    }
}

$logo = '';
$logoStmt = $pdo->query("SELECT value FROM settings WHERE `key`='logo'");
if ($row = $logoStmt->fetch(PDO::FETCH_ASSOC)) {
    $logo = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Giriş Yap</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
<style>
body {background: linear-gradient(135deg, #f0f4f8, #d9e2ec); font-family: 'Poppins', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;}
.login-box {background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center;}
.login-logo img {max-width: 140px; height: auto; margin-bottom: 20px;}
</style>
</head>
<body>
<div class="login-box">
  <div class="login-logo">
    <?php if ($logo): ?><img src="<?= htmlspecialchars($logo) ?>" alt="Logo"><?php endif; ?>
  </div>
  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
  <form method="post">
    <div class="mb-3 text-start">
      <label class="form-label">E-Posta Adresi</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3 text-start">
      <label class="form-label">Şifre</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

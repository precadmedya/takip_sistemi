<?php
require __DIR__ . '/includes/auth.php';

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
    $stmt->execute([$id]);
    header('Location: /users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $role = $_POST['role'];
    if ($action === 'edit' && $id) {
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET email=?, password=?, role=? WHERE id=?');
            $stmt->execute([$email, $pass, $role, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET email=?, role=? WHERE id=?');
            $stmt->execute([$email, $role, $id]);
        }
    } else {
        $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
        $stmt->execute([$email, $pass, $role]);
    }
    header('Location: /users.php');
    exit;
}

$editUser = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([$id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

$users = $pdo->query('SELECT * FROM users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
<h1>Kullanıcılar</h1>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>ID</th>
      <th>E-Posta</th>
      <th>Rol</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= $u['role'] ?></td>
      <td>
        <a href="/users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
        <a href="/users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')">Sil</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<hr>
<h2><?= $editUser ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı' ?></h2>
<form method="post">
  <div class="mb-3">
    <label class="form-label">E-Posta</label>
    <input type="email" name="email" class="form-control" value="<?= $editUser['email'] ?? '' ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Şifre<?= $editUser ? ' (değiştirmek için doldur)' : '' ?></label>
    <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
  </div>
  <div class="mb-3">
    <label class="form-label">Rol</label>
    <select name="role" class="form-control">
      <option value="admin" <?= isset($editUser) && $editUser['role']==='admin' ? 'selected' : '' ?>>Admin</option>
      <option value="user" <?= isset($editUser) && $editUser['role']==='user' ? 'selected' : '' ?>>User</option>
    </select>
  </div>
  <button type="submit" class="btn btn-primary">Kaydet</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

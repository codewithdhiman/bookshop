<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $error = 'Invalid CSRF'; }
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = 1 LIMIT 1');
    $stmt->execute([$_POST['email'] ?? '']);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password']) && $user['role'] === 'admin') {
      session_regenerate_id(true);
      $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role']];
      header('Location: ' . base_url('admin/index.php')); exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
<link href="<?= base_url('public/css/design.css') ?>" rel="stylesheet">
<link href="<?= base_url('public/css/style.css') ?>" rel="stylesheet">
</head><body class="bg-light">
<div class="container py-5">
  <div class="row"><div class="col-md-4 offset-md-4">
    <h3>Admin Login</h3>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3"><label class="form-label">Email</label><input name="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
      <button class="btn btn-primary">Login</button>
    </form>
  </div></div>
</div>
</body></html>

<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF'; }
  [$ok, $msg] = login_user($_POST['email'] ?? '', $_POST['password'] ?? '');
  if ($ok) { header('Location: ' . base_url()); exit; } else { $errors[] = $msg; }
}

?>
<div class="row">
  <div class="col-md-4 offset-md-4">
    <h3>Login</h3>
    <?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?= esc($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
      <button class="btn btn-primary">Login</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

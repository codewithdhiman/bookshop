<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF'; }
    [$ok, $msg] = register_user([ 'name'=>$_POST['name'] ?? '', 'email'=>$_POST['email'] ?? '', 'password'=>$_POST['password'] ?? '', 'phone'=>$_POST['phone'] ?? '' ]);
    if ($ok) { $_SESSION['flash_success']=$msg; header('Location: ' . base_url('login.php')); exit; } else { $errors[] = $msg; }
}

?>
<div class="row">
  <div class="col-md-6 offset-md-3">
    <h3>Register</h3>
    <?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
      <button class="btn btn-primary">Register</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

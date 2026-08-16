<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token'; }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
    $status = ($_POST['status'] ?? '1') === '1' ? 1 : 0;

    if ($name === '' || strlen($name) < 2) $errors[] = 'Name required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if ($password === '' || $password !== $confirm || strlen($password) < 6) $errors[] = 'Password must be at least 6 chars and match confirmation';

    // unique email
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1'); $st->execute([$email]);
    if ($st->fetch()) $errors[] = 'Email already in use';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (name,email,password,phone,role,status) VALUES (?,?,?,?,?,?)');
        $ins->execute([$name,$email,$hash,$phone,$role,$status]);
        // audit log
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED, action VARCHAR(100), target_user_id BIGINT UNSIGNED, ip VARCHAR(45), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Exception $e) {}
        $aid = $_SESSION['user']['id'] ?? null;
        $log = $pdo->prepare('INSERT INTO admin_audit (admin_id,action,target_user_id,ip) VALUES (?,?,?,?)');
        $log->execute([$aid,'USER_CREATED',$pdo->lastInsertId(),$_SERVER['REMOTE_ADDR'] ?? null]);
        $_SESSION['flash_success'] = 'User created successfully.';
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }
}
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
  <div class="card-body">
    <h5>Add User</h5>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= esc($e) ?></div><?php endforeach; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3"><label class="form-label">Full name</label><input name="name" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input name="email" class="form-control" type="email" required></div>
      <div class="mb-3"><label class="form-label">Mobile</label><input name="phone" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Password</label><input name="password" class="form-control" type="password" required></div>
      <div class="mb-3"><label class="form-label">Confirm Password</label><input name="confirm" class="form-control" type="password" required></div>
      <div class="mb-3"><label class="form-label">Role</label><select name="role" class="form-select"><option value="customer">Customer</option><option value="admin">Admin</option></select></div>
      <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <button class="btn btn-primary">Create user</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

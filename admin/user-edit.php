<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
require_admin();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT id,name,email,phone,role,status,two_factor_enabled FROM users WHERE id = ? LIMIT 1');
$st->execute([$id]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: ' . base_url('admin/users.php')); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token'; }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
    $status = ($_POST['status'] ?? '1') === '1' ? 1 : 0;
    $newpw = $_POST['password'] ?? '';

    if ($name === '' || strlen($name) < 2) $errors[] = 'Name required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    // check email uniqueness
    $st2 = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1'); $st2->execute([$email,$id]);
    if ($st2->fetch()) $errors[] = 'Email already in use';

    // admin self-protection
    $me = $_SESSION['user']['id'] ?? null;
    if ($me == $id && $role !== 'admin') { $errors[] = 'You cannot remove your own admin role'; }

    if (empty($errors)) {
        $sql = 'UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ?';
        $params = [$name,$email,$phone,$role,$status,$id];
        if (!empty($newpw)) {
            $hash = password_hash($newpw, PASSWORD_DEFAULT);
            $sql = 'UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ?, password = ? WHERE id = ?';
            $params = [$name,$email,$phone,$role,$status,$hash,$id];
        } else {
            $sql .= ' WHERE id = ?';
        }
        $pdo->prepare($sql)->execute($params);
        // audit
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED, action VARCHAR(100), target_user_id BIGINT UNSIGNED, ip VARCHAR(45), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Exception $e) {}
        $aid = $_SESSION['user']['id'] ?? null;
        $log = $pdo->prepare('INSERT INTO admin_audit (admin_id,action,target_user_id,ip) VALUES (?,?,?,?)');
        $log->execute([$aid,'USER_UPDATED',$id,$_SERVER['REMOTE_ADDR'] ?? null]);
        $_SESSION['flash_success'] = 'User updated successfully.';
        header('Location: ' . base_url('admin/users.php'));
        exit;
    }
}
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
  <div class="card-body">
    <h5>Edit User</h5>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= esc($e) ?></div><?php endforeach; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3"><label class="form-label">Full name</label><input name="name" value="<?= esc($user['name']) ?>" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input name="email" value="<?= esc($user['email']) ?>" class="form-control" type="email" required></div>
      <div class="mb-3"><label class="form-label">Mobile</label><input name="phone" value="<?= esc($user['phone']) ?>" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Set New Password (leave blank to keep existing)</label><input name="password" class="form-control" type="password"></div>
      <div class="mb-3"><label class="form-label">Role</label><select name="role" class="form-select"><option value="customer" <?= $user['role']==='customer'?'selected':'' ?>>Customer</option><option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option></select></div>
      <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="1" <?= $user['status']?'selected':'' ?>>Active</option><option value="0" <?= !$user['status']?'selected':'' ?>>Inactive</option></select></div>
      <button class="btn btn-primary">Save changes</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

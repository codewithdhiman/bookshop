<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
require_admin();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT id,name,email,phone,role,status,created_at,two_factor_enabled,two_factor_verified_at FROM users WHERE id = ? LIMIT 1');
$st->execute([$id]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: ' . base_url('admin/users.php')); exit; }

// recent orders
$orders = [];
$ost = $pdo->prepare('SELECT id,order_number,total_amount,order_status,created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 10');
$ost->execute([$id]);
$orders = $ost->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
  <div class="card-body">
    <h5>User details</h5>
    <dl class="row">
      <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?= esc($user['name']) ?></dd>
      <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= esc($user['email']) ?></dd>
      <dt class="col-sm-3">Mobile</dt><dd class="col-sm-9"><?= esc($user['phone']) ?></dd>
      <dt class="col-sm-3">Role</dt><dd class="col-sm-9"><?= esc($user['role']) ?></dd>
      <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?= $user['status']?'<span class="badge bg-success">Active</span>':'<span class="badge bg-warning">Inactive</span>' ?></dd>
      <dt class="col-sm-3">Registered</dt><dd class="col-sm-9"><?= esc($user['created_at']) ?></dd>
    </dl>

    <h6>Security</h6>
    <dl class="row">
      <dt class="col-sm-3">2FA Status</dt><dd class="col-sm-9"><?= !empty($user['two_factor_enabled'])?'<span class="badge bg-success">Enabled</span>':'<span class="badge bg-secondary">Disabled</span>' ?></dd>
      <?php if (!empty($user['two_factor_verified_at'])): ?><dt class="col-sm-3">2FA Verified</dt><dd class="col-sm-9"><?= esc($user['two_factor_verified_at']) ?></dd><?php endif; ?>
    </dl>

    <h6>Recent Orders</h6>
    <?php if ($orders): ?>
      <table class="table table-sm">
        <thead><tr><th>Order</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr><td><?= esc($o['order_number']) ?></td><td><?= esc($o['created_at']) ?></td><td><?= esc($o['total_amount']) ?></td><td><?= esc($o['order_status']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted">No recent orders.</p>
    <?php endif; ?>

  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

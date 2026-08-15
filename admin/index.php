<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_header.php';

require_admin();
$pdo = getPDO();
$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$activeProducts = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE status = 1')->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$customers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$lowStock = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock < 5')->fetchColumn();

?>
<h3 class="mb-4">Admin Dashboard</h3>
<div class="row g-3">
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3 text-center">
      <div class="text-muted">Total Products</div>
      <div class="h4 mb-0"><?= $totalProducts ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3 text-center">
      <div class="text-muted">Active Products</div>
      <div class="h4 mb-0"><?= $activeProducts ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3 text-center">
      <div class="text-muted">Total Orders</div>
      <div class="h4 mb-0"><?= $totalOrders ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3 text-center">
      <div class="text-muted">Pending Orders</div>
      <div class="h4 mb-0"><?= $pendingOrders ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3 text-center">
      <div class="text-muted">Customers</div>
      <div class="h4 mb-0"><?= $customers ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3 text-center">
      <div class="text-muted">Low Stock</div>
      <div class="h4 mb-0"><?= $lowStock ?></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

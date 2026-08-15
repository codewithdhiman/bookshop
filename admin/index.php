<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

require_admin();
$pdo = getPDO();
$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$activeProducts = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE status = 1')->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$customers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$lowStock = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock < 5')->fetchColumn();

?>
<h3>Admin Dashboard</h3>
<div class="row">
  <div class="col-md-3 mb-3"><div class="card p-3">Total Products<br><strong><?= $totalProducts ?></strong></div></div>
  <div class="col-md-3 mb-3"><div class="card p-3">Active Products<br><strong><?= $activeProducts ?></strong></div></div>
  <div class="col-md-3 mb-3"><div class="card p-3">Total Orders<br><strong><?= $totalOrders ?></strong></div></div>
  <div class="col-md-3 mb-3"><div class="card p-3">Pending Orders<br><strong><?= $pendingOrders ?></strong></div></div>
  <div class="col-md-3 mb-3"><div class="card p-3">Customers<br><strong><?= $customers ?></strong></div></div>
  <div class="col-md-3 mb-3"><div class="card p-3">Low Stock<br><strong><?= $lowStock ?></strong></div></div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

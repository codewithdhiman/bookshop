<?php
// includes/navbar.php
require_once __DIR__ . '/functions.php';
$user = $_SESSION['user'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand" href="<?= base_url() ?>">Bookshop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('products.php') ?>">Books</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('products.php') ?>">Categories</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('cart.php') ?>">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?: 0 ?>)</a></li>
        <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminMenu">
              <li><a class="dropdown-item" href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
              <li><a class="dropdown-item" href="<?= base_url('admin/products/index.php') ?>">Products</a></li>
              <li><a class="dropdown-item" href="<?= base_url('admin/categories/index.php') ?>">Categories</a></li>
              <li><a class="dropdown-item" href="<?= base_url('admin/orders/index.php') ?>">Orders</a></li>
              <li><a class="dropdown-item" href="<?= base_url('admin/sales.php') ?>">Sales Report</a></li>
              <li><a class="dropdown-item" href="<?= base_url('admin/tax.php') ?>">Tax Management</a></li>
            </ul>
          </li>
        <?php endif; ?>
        <?php if ($user): ?>
            <li class="nav-item"><a class="nav-link" href="#">Hello, <?= esc($user['name']) ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('logout.php') ?>">Logout</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('login.php') ?>">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('register.php') ?>">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

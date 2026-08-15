<?php
// includes/navbar.php
require_once __DIR__ . '/functions.php';
$user = $_SESSION['user'] ?? null;
?>
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="<?= base_url() ?>">Bookshop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('products.php') ?>">Books</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('products.php') ?>">Categories</a></li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-2">
          <form class="d-flex" method="get" action="<?= base_url('products.php') ?>">
            <div class="input-group input-group-sm">
              <input name="search" value="<?= esc($_GET['search'] ?? '') ?>" class="form-control form-control-sm" placeholder="Search books...">
              <button class="btn btn-outline-secondary" type="submit">Search</button>
            </div>
          </form>
        </li>

        <li class="nav-item me-2">
          <a class="nav-link d-flex align-items-center" href="<?= base_url('cart.php') ?>" aria-label="Cart">
            <!-- simple cart icon -->
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 6h15l-1.5 9h-12L4 2H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="ms-2">Cart</span>
            <span class="badge bg-secondary ms-2" id="cart-count"><?= array_sum($_SESSION['cart'] ?? []) ?: 0 ?></span>
          </a>
        </li>

        <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
          <li class="nav-item dropdown me-2">
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
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="accountMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">Hello, <?= esc($user['name']) ?></a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountMenu">
                <li><a class="dropdown-item" href="<?= base_url('my-orders.php') ?>">My Orders</a></li>
                <li><a class="dropdown-item" href="<?= base_url('addresses.php') ?>">Addresses</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= base_url('logout.php') ?>">Logout</a></li>
              </ul>
            </li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('login.php') ?>">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('register.php') ?>">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

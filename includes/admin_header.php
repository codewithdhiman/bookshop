<?php
// includes/admin_header.php
require_once __DIR__ . '/functions.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — Bookshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('public/css/design.css') ?>" rel="stylesheet">
    <link href="<?= base_url('public/css/style.css') ?>" rel="stylesheet">
</head>
<body>
  <div class="d-flex" style="min-height:100vh;gap:24px;padding:20px;background:var(--background)">
    <aside class="admin-sidebar" role="navigation" aria-label="Admin sidebar">
      <nav class="nav flex-column">
      <a class="nav-link" href="<?= base_url('admin/index.php') ?>">Dashboard</a>
      <a class="nav-link" href="<?= base_url('admin/users.php') ?>">Users</a>
        <a class="nav-link" href="<?= base_url('admin/products/index.php') ?>">Products</a>
        <a class="nav-link" href="<?= base_url('admin/categories/index.php') ?>">Categories</a>
        <a class="nav-link" href="<?= base_url('admin/orders/index.php') ?>">Orders</a>
        <a class="nav-link" href="<?= base_url('admin/sales.php') ?>">Sales</a>
        <a class="nav-link" href="<?= base_url('admin/tax.php') ?>">Tax</a>
        <a class="nav-link" href="<?= base_url('admin/seo/index.php') ?>">SEO</a>
        <a class="nav-link" href="<?= base_url('admin/logout.php') ?>">Logout</a>
      </nav>
    </aside>

      <div style="flex:1">
      <div class="admin-overlay" id="adminOverlay"></div>
      <header class="admin-topbar d-flex align-items-center justify-content-between mb-4">
        <button id="admin-menu-toggle" class="btn btn-outline-primary d-lg-none me-2" aria-label="Open admin menu">☰</button>
        <div>
          <h2 class="h5 mb-0">Admin</h2>
          <small class="text-muted-2">Manage your store</small>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="text-muted">Signed in as <?= esc($_SESSION['user']['name'] ?? 'Admin') ?></div>
        </div>
      </header>

      <main class="container-fluid">
        <div class="row">
          <div class="col-12">
            <?php if (!empty($_SESSION['flash'])): ?>
              <script>
                document.addEventListener('DOMContentLoaded', function(){
                  <?php foreach ((array)$_SESSION['flash'] as $f): ?>
                    window.toast(<?= json_encode($f) ?>);
                  <?php endforeach; ?>
                });
              </script>
              <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

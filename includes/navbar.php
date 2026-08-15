<?php
// includes/navbar.php
require_once __DIR__ . '/functions.php';
$user = $_SESSION['user'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand" href="/">Bookshop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/products.php">Books</a></li>
        <li class="nav-item"><a class="nav-link" href="/products.php">Categories</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/cart.php">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?: 0 ?>)</a></li>
        <?php if ($user): ?>
            <li class="nav-item"><a class="nav-link" href="#">Hello, <?= esc($user['name']) ?></a></li>
            <li class="nav-item"><a class="nav-link" href="/logout.php">Logout</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="/login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

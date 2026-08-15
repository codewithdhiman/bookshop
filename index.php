<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$pdo = getPDO();
// featured: latest 4 active products
$stmt = $pdo->query('SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC LIMIT 4');
$featured = $stmt->fetchAll();
?>
<div class="row">
  <div class="col-12 mb-4">
    <div class="p-5 bg-light rounded-3 text-center">
      <h1>Welcome to Bookshop</h1>
      <p class="lead">Find your next favorite book.</p>
      <a href="/products.php" class="btn btn-primary">Browse Books</a>
    </div>
  </div>

  <div class="col-12">
    <h3>Featured Books</h3>
    <div class="row">
      <?php foreach ($featured as $p): ?>
        <div class="col-md-3 mb-3">
          <div class="card h-100">
            <img src="/public/uploads/<?= esc($p['cover_image'] ?? 'placeholder.png') ?>" class="card-img-top" alt="<?= esc($p['title']) ?>">
            <div class="card-body">
              <h5 class="product-title"><?= esc($p['title']) ?></h5>
              <p class="small muted"><?= esc($p['author']) ?></p>
              <p class="mb-0"><?= currency_format($p['discount_price'] ?: $p['price']) ?></p>
              <a href="/product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">View</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

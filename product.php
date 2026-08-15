<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /products.php'); exit;
}
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.status = 1 LIMIT 1');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { echo '<div class="alert alert-danger">Product not found.</div>'; require __DIR__ . '/includes/footer.php'; exit; }

?>
<div class="row">
  <div class="col-md-5">
    <img src="/public/uploads/<?= esc($p['cover_image'] ?? 'placeholder.png') ?>" class="img-fluid" alt="<?= esc($p['title']) ?>">
  </div>
  <div class="col-md-7">
    <h2><?= esc($p['title']) ?></h2>
    <p class="small muted">By <?= esc($p['author']) ?> | <?= esc($p['publisher']) ?></p>
    <p>
      <?php if (!empty($p['discount_price'])): ?>
        <span class="h4 text-danger"><?= currency_format($p['discount_price']) ?></span>
        <small class="text-muted text-decoration-line-through ms-2"><?= currency_format($p['price']) ?></small>
      <?php else: ?>
        <span class="h4"><?= currency_format($p['price']) ?></span>
      <?php endif; ?>
    </p>
    <p><?= nl2br(esc($p['description'])) ?></p>
    <p>
      <?php if ($p['stock'] <= 0): ?>
        <span class="badge bg-danger">Out of Stock</span>
      <?php else: ?>
        <span class="badge bg-success">In Stock: <?= $p['stock'] ?></span>
      <?php endif; ?>
    </p>

    <?php if ($p['stock'] > 0): ?>
      <form method="post" action="/cart.php" class="row g-2 align-items-center">
        <div class="col-auto">
          <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>" class="form-control" style="width:100px">
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Add to Cart</button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

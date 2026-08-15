<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
  header('Location: ' . base_url('products.php')); exit;
}
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.status = 1 LIMIT 1');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { echo '<div class="alert alert-danger">Product not found.</div>'; require __DIR__ . '/includes/footer.php'; exit; }

?>
<div class="row product-detail">
  <div class="col-12 col-md-5 mb-3">
    <div class="cover"><img src="<?= base_url('public/uploads/' . ($p['cover_image'] ?? 'placeholder.png')) ?>" class="img-fluid" alt="<?= esc($p['title']) ?>"></div>
  </div>
  <div class="col-12 col-md-7">
    <h2><?= esc($p['title']) ?></h2>
    <p class="small muted">By <?= esc($p['author']) ?><?= $p['publisher']? ' | '.esc($p['publisher']):'' ?></p>
    <div class="mb-3">
      <?php if (!empty($p['discount_price'])): ?>
        <div class="h4 text-danger d-inline-block me-2"><?= currency_format($p['discount_price']) ?></div>
        <small class="text-muted text-decoration-line-through"><?= currency_format($p['price']) ?></small>
      <?php else: ?>
        <div class="h4 d-inline-block"><?= currency_format($p['price']) ?></div>
      <?php endif; ?>
    </div>

    <div class="specs">
      <p><?= nl2br(esc($p['description'])) ?></p>
    </div>

    <p class="mt-2">
      <?php if ($p['stock'] <= 0): ?>
        <span class="badge bg-danger">Out of Stock</span>
      <?php else: ?>
        <span class="badge bg-success">In Stock: <?= $p['stock'] ?></span>
      <?php endif; ?>
    </p>

    <?php if ($p['stock'] > 0): ?>
      <form method="post" action="<?= base_url('cart.php') ?>" class="row g-2 align-items-center">
        <div class="col-auto">
          <div class="qty-control d-flex align-items-center">
            <button type="button" class="btn btn-outline-secondary qty-step" data-step="-1">-</button>
            <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>" class="form-control text-center" style="width:88px">
            <button type="button" class="btn btn-outline-secondary qty-step" data-step="1">+</button>
          </div>
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-lg btn-primary">Add to Cart</button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>

<!-- Mobile sticky add-to-cart -->
<?php if ($p['stock'] > 0): ?>
  <div class="pdp-spacer d-md-none"></div>
  <div class="pdp-sticky d-md-none" aria-hidden="false">
    <div class="thumb"><img src="<?= base_url('public/uploads/' . ($p['cover_image'] ?? 'placeholder.png')) ?>" alt="<?= esc($p['title']) ?>"></div>
    <div class="meta">
      <div class="title"><?= esc($p['title']) ?></div>
      <div class="price"><?= !empty($p['discount_price'])? currency_format($p['discount_price']) : currency_format($p['price']) ?></div>
    </div>
    <div class="actions">
      <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>" class="form-control" style="width:72px">
      <button class="btn btn-primary" type="submit">Add</button>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

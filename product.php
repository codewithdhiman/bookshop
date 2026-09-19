<?php
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
  header('Location: ' . base_url('products.php')); exit;
}
require_once __DIR__ . '/includes/header.php';
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.status = 1 LIMIT 1');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { echo '<div class="alert alert-danger">Product not found.</div>'; require __DIR__ . '/includes/footer.php'; exit; }

$title = $p['title'] ?? 'Untitled Book';
$author = $p['author'] ?? 'Unknown Author';
$cover = $p['cover_image'] ?? 'placeholder.png';
$publisher = $p['publisher'] ?? '';
$description = $p['description'] ?? '';
$price = (float)($p['price'] ?? 0);
$discountPrice = !empty($p['discount_price']) ? (float)$p['discount_price'] : null;
$stock = (int)($p['stock'] ?? 0);
$productId = (int)($p['id'] ?? 0);

?>
<div class="row product-detail">
  <div class="col-12 col-md-5 mb-3">
    <div class="cover"><img src="<?= base_url('public/uploads/' . $cover) ?>" class="img-fluid" alt="<?= esc($title) ?>"></div>
  </div>
  <div class="col-12 col-md-7">
    <h2><?= esc($title) ?></h2>
    <p class="small muted">By <?= esc($author) ?><?= $publisher ? ' | '.esc($publisher):'' ?></p>
    <div class="mb-3">
      <?php if ($discountPrice !== null): ?>
        <div class="h4 text-danger d-inline-block me-2"><?= currency_format($discountPrice) ?></div>
        <small class="text-muted text-decoration-line-through"><?= currency_format($price) ?></small>
      <?php else: ?>
        <div class="h4 d-inline-block"><?= currency_format($price) ?></div>
      <?php endif; ?>
    </div>

    <div class="specs">
      <p><?= nl2br(esc($description)) ?></p>
    </div>

    <p class="mt-2">
      <?php if ($stock <= 0): ?>
        <span class="badge bg-danger">Out of Stock</span>
      <?php else: ?>
        <span class="badge bg-success">In Stock: <?= $stock ?></span>
      <?php endif; ?>
    </p>

    <?php if ($stock > 0): ?>
      <form method="post" action="<?= base_url('cart.php') ?>" class="row g-2 align-items-center">
        <div class="col-auto">
          <div class="qty-control d-flex align-items-center">
            <button type="button" class="btn btn-outline-secondary qty-step" data-step="-1">-</button>
            <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" class="form-control text-center" style="width:88px">
            <button type="button" class="btn btn-outline-secondary qty-step" data-step="1">+</button>
          </div>
          <input type="hidden" name="product_id" value="<?= $productId ?>">
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
<?php if ($stock > 0): ?>
  <div class="pdp-spacer d-md-none"></div>
  <div class="pdp-sticky d-md-none" aria-hidden="false">
    <div class="thumb"><img src="<?= base_url('public/uploads/' . $cover) ?>" alt="<?= esc($title) ?>"></div>
    <div class="meta">
      <div class="title"><?= esc($title) ?></div>
      <div class="price"><?= $discountPrice !== null ? currency_format($discountPrice) : currency_format($price) ?></div>
    </div>
    <div class="actions">
      <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" class="form-control" style="width:72px">
      <button class="btn btn-primary" type="submit">Add</button>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

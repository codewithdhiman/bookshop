<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$pdo = getPDO();
// featured: latest 6 active products
$stmt = $pdo->query('SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC LIMIT 6');
$featured = $stmt->fetchAll();
$cats = $pdo->query('SELECT * FROM categories WHERE status = 1 LIMIT 4')->fetchAll();
?>

<!-- Hero -->
<section class="hero py-5">
  <div class="row align-items-center">
    <div class="col-12 col-md-7">
      <div class="lead-label text-uppercase small text-muted-2">The joy of reading</div>
      <h1 class="display-5 mb-3">Find Your Next<br>Great Read.</h1>
      <p class="lead text-muted mb-4">Stories, ideas and knowledge — thoughtfully collected for curious readers.</p>
      <div class="d-flex gap-2">
        <a href="<?= base_url('products.php') ?>" class="btn btn-primary btn-lg">Browse Books</a>
        <a href="<?= base_url('products.php') ?>" class="btn btn-outline-primary btn-lg">Explore Categories</a>
      </div>
    </div>
    <div class="col-12 col-md-5">
      <div class="hero-visual">
        <?php foreach ($featured as $i => $p): ?>
          <img src="<?= base_url('public/uploads/' . ($p['cover_image'] ?? 'placeholder.png')) ?>" alt="<?= esc($p['title']) ?>" class="hero-book" style="--i:<?= $i ?>">
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- Featured categories -->
<section class="mb-5">
  <h3 class="mb-3">Explore Categories</h3>
  <div class="row gx-3 gy-3">
    <?php foreach ($cats as $c): ?>
      <div class="col-6 col-md-3">
        <a href="<?= base_url('products.php?category=' . esc($c['slug'])) ?>" class="card p-3 text-decoration-none h-100 category-card">
          <div class="fw-bold mb-1"><?= esc($c['name']) ?></div>
          <div class="small text-muted"><?= esc(substr($c['description'] ?? '',0,60)) ?></div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Trending books -->
<section>
  <h3 class="mb-3">Featured Books</h3>
  <div class="product-grid">
    <div class="row">
      <?php foreach ($featured as $p): ?>
        <?php
          $title = $p['title'] ?? 'Untitled Book';
          $author = $p['author'] ?? 'Unknown Author';
          $cover = $p['cover_image'] ?? 'placeholder.png';
          $price = (float)($p['price'] ?? 0);
          $discountPrice = !empty($p['discount_price']) ? (float)$p['discount_price'] : null;
          $stock = (int)($p['stock'] ?? 0);
          $productId = (int)($p['id'] ?? 0);
        ?>
        <div class="col-md-3 mb-3">
          <div class="card h-100">
            <img src="<?= base_url('public/uploads/' . $cover) ?>" class="card-img-top" alt="<?= esc($title) ?>">
            <div class="card-body d-flex flex-column">
              <h5 class="product-title"><?= esc($title) ?></h5>
              <p class="small muted mb-1"><?= esc($author) ?></p>
              <p class="mb-1">
                <?php if ($discountPrice !== null): ?>
                  <span class="fw-bold"><?= currency_format($discountPrice) ?></span>
                  <small class="text-muted text-decoration-line-through ms-2"><?= currency_format($price) ?></small>
                <?php else: ?>
                  <span class="fw-bold"><?= currency_format($price) ?></span>
                <?php endif; ?>
              </p>
              <div class="mt-auto">
                <a href="<?= base_url('product.php?id=' . $productId) ?>" class="btn btn-sm btn-outline-primary">View</a>
                <?php if ($stock > 0): ?>
                  <form method="post" action="<?= base_url('cart.php') ?>" class="d-inline ms-2">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-primary">Add to Cart</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

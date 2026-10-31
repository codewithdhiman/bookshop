<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$pdo = getPDO();
ensure_product_columns($pdo);
ensure_product_images_table($pdo);
// Featured products are selected explicitly in the admin product form.
$stmt = $pdo->query('SELECT p.*, COALESCE(primary_image.image, p.cover_image) AS listing_image FROM products p LEFT JOIN (SELECT product_id, MAX(image) AS image FROM product_images WHERE is_primary = 1 GROUP BY product_id) primary_image ON primary_image.product_id = p.id WHERE p.status = 1 AND p.is_featured = 1 ORDER BY p.created_at DESC LIMIT 6');
$featured = $stmt->fetchAll();
$cats = $pdo->query('SELECT * FROM categories WHERE status = 1 LIMIT 4')->fetchAll();
?>

<!-- Hero -->
<section class="hero py-5">
  <div class="row align-items-center">
    <div class="col-12 col-md-7">
      <div class="lead-label text-uppercase small text-muted-2">THE ART OF EXPRESSION</div>
      <h1 class="display-5 mb-3">Craft Your Next Masterpiece..</h1>
      <p class="lead text-muted mb-4">Handcrafted sketchbooks, artisan drawing inks, and beautifully published works—designed to inspire curious minds and creative hands.</p>
      <div class="d-flex gap-2">
        <a href="<?= base_url('products.php') ?>" class="btn btn-primary btn-lg">Shop the Collection</a>
        <!-- <a href="<?= base_url('products.php') ?>" class="btn btn-outline-primary btn-lg">Explore Publications</a> -->
      </div>
    </div>
    <div class="col-12 col-md-5">
      <div class="hero-visual">
        <?php foreach ($featured as $i => $p): ?>
          <a href="<?= product_url((string)$p['slug']) ?>" class="hero-book" aria-label="View <?= esc($p['title']) ?>">
            <img src="<?= base_url('public/uploads/' . ($p['listing_image'] ?? $p['cover_image'] ?? 'placeholder.png')) ?>" alt="<?= esc($p['title']) ?>" class="hero-book-image" style="--i:<?= $i ?>">
          </a>
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
          $cover = $p['listing_image'] ?? $p['cover_image'] ?? 'placeholder.png';
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
                <a href="<?= product_url((string)$p['slug']) ?>" class="btn btn-sm btn-outline-primary">View</a>
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

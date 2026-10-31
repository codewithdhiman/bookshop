<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$pdo = getPDO();
ensure_product_images_table($pdo);

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$params = [];
$where = 'WHERE p.status = 1';

if (!empty($_GET['category'])) {
    $where .= ' AND c.slug = ?';
    $params[] = $_GET['category'];
}
if (!empty($_GET['search'])) {
    $where .= ' AND (p.title LIKE ? OR p.author LIKE ?)';
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}

$order = 'p.created_at DESC';
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] === 'price_low') $order = 'p.discount_price ASC, p.price ASC';
    if ($_GET['sort'] === 'price_high') $order = 'p.discount_price DESC, p.price DESC';
    if ($_GET['sort'] === 'newest') $order = 'p.created_at DESC';
}

$sql = "SELECT SQL_CALC_FOUND_ROWS p.* , c.name as category_name, c.slug as category_slug,
  COALESCE(primary_image.image, p.cover_image) AS listing_image
        FROM products p
        JOIN categories c ON c.id = p.category_id
  LEFT JOIN (SELECT product_id, MAX(image) AS image FROM product_images WHERE is_primary = 1 GROUP BY product_id) primary_image ON primary_image.product_id = p.id
        $where
        ORDER BY $order
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
$pages = (int)ceil($total / $perPage);

?>
<div class="products-grid">
  <div class="page-header grid-full d-flex justify-content-between align-items-center mb-3">
    <h3>Books</h3>
    <div class="d-flex align-items-center">
      <button id="filters-open" class="btn btn-sm btn-outline-secondary d-md-none me-2">Filters</button>
      <form class="d-flex d-none d-md-flex" method="get" action="<?= base_url('products.php') ?>">
        <input name="search" value="<?= esc($_GET['search'] ?? '') ?>" class="form-control form-control-sm me-2" placeholder="Search by title or author">
        <button class="btn btn-sm btn-outline-secondary">Search</button>
      </form>
    </div>
  </div>

  <!-- Offcanvas filters -->
  <div id="filters-offcanvas" class="filters-offcanvas">
    <div class="offcanvas-header d-flex justify-content-between align-items-center">
      <strong>Filters</strong>
      <button id="filters-close" class="btn btn-sm btn-light">Close</button>
    </div>
    <div class="offcanvas-body">
      <form method="get" action="<?= base_url('products.php') ?>">
        <div class="mb-2">
          <label class="form-label small">Category</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach ($pdo->query('SELECT * FROM categories WHERE status=1')->fetchAll() as $cat): ?>
              <option value="<?= esc($cat['slug']) ?>" <?= (($_GET['category'] ?? '')===$cat['slug'])? 'selected':'' ?>><?= esc($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label small">Sort</label>
          <select name="sort" class="form-select form-select-sm">
            <option value="">Default</option>
            <option value="price_low" <?= (($_GET['sort'] ?? '')==='price_low')? 'selected':'' ?>>Price: Low to High</option>
            <option value="price_high" <?= (($_GET['sort'] ?? '')==='price_high')? 'selected':'' ?>>Price: High to Low</option>
            <option value="newest" <?= (($_GET['sort'] ?? '')==='newest')? 'selected':'' ?>>Newest</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary btn-sm">Apply</button>
          <a href="<?= base_url('products.php') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>
  <div id="filters-backdrop" class="filters-backdrop"></div>

  <?php if (empty($products)): ?>
    <div class="grid-full empty-state">
      <div class="card p-4 text-center">
        <h4>No books found</h4>
        <p class="text-muted">Try searching for another title, author, or category.</p>
        <a href="<?= base_url('products.php') ?>" class="btn btn-outline-primary">Browse All Books</a>
      </div>
    </div>
  <?php endif; ?>

  <?php foreach ($products as $p): ?>
    <?php
      $productId = (int)($p['id'] ?? 0);
      $title = $p['title'] ?? 'Untitled Book';
      $author = $p['author'] ?? 'Unknown Author';
      $cover = $p['listing_image'] ?? $p['cover_image'] ?? 'placeholder.png';
      $price = (float)($p['price'] ?? 0);
      $discountPrice = !empty($p['discount_price']) ? (float)$p['discount_price'] : null;
      $stock = (int)($p['stock'] ?? 0);
    ?>
    <div class="product-item">
      <div class="card h-100 product-card">
        <a href="<?= product_url((string)$p['slug']) ?>" class="text-decoration-none text-reset">
          <img src="<?= base_url('public/uploads/' . $cover) ?>" alt="<?= esc($title) ?>">
          <div class="card-body d-flex flex-column">
            <h5 class="product-title"><?= esc($title) ?></h5>
            <div class="product-meta small muted"><?= esc($author) ?></div>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <div>
                <?php if ($discountPrice !== null): ?>
                  <div class="fw-bold text-danger"><?= currency_format($discountPrice) ?></div>
                  <div class="small text-muted text-decoration-line-through"><?= currency_format($price) ?></div>
                <?php else: ?>
                  <div class="fw-bold"><?= currency_format($price) ?></div>
                <?php endif; ?>
              </div>
              <div>
                <?php if ($stock <= 0): ?>
                  <span class="badge bg-danger">Out</span>
                <?php else: ?>
                  <span class="badge bg-success">In</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </a>
        <div class="card-footer bg-transparent border-0 pt-0">
          <form method="post" action="<?= base_url('cart.php') ?>" class="d-flex gap-2">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $productId ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <a href="<?= product_url((string)$p['slug']) ?>" class="btn btn-sm btn-outline-primary flex-grow-1">View</a>
            <button type="submit" class="btn btn-sm btn-primary" <?= $stock<=0? 'disabled':'' ?>>Add</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="grid-full">
    <nav>
      <ul class="pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?>
          <li class="page-item <?= $i===$page? 'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

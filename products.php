<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$pdo = getPDO();

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

$sql = "SELECT SQL_CALC_FOUND_ROWS p.* , c.name as category_name, c.slug as category_slug
        FROM products p
        JOIN categories c ON c.id = p.category_id
        $where
        ORDER BY $order
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
$pages = (int)ceil($total / $perPage);

?>
<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center mb-3">
    <h3>Books</h3>
    <form class="d-flex" method="get" action="<?= base_url('products.php') ?>">
      <input name="search" value="<?= esc($_GET['search'] ?? '') ?>" class="form-control form-control-sm me-2" placeholder="Search by title or author">
      <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
  </div>

  <?php if (empty($products)): ?>
    <div class="col-12">No products found.</div>
  <?php endif; ?>

  <?php foreach ($products as $p): ?>
    <div class="col-md-3 mb-3">
      <div class="card h-100">
        <img src="<?= base_url('public/uploads/' . ($p['cover_image'] ?? 'placeholder.png')) ?>" class="card-img-top" alt="<?= esc($p['title']) ?>">
        <div class="card-body d-flex flex-column">
          <h5 class="product-title"><?= esc($p['title']) ?></h5>
          <p class="small muted mb-1"><?= esc($p['author']) ?></p>
          <p class="mb-1">
            <?php if (!empty($p['discount_price'])): ?>
              <span class="fw-bold"><?= currency_format($p['discount_price']) ?></span>
              <small class="text-muted text-decoration-line-through ms-2"><?= currency_format($p['price']) ?></small>
            <?php else: ?>
              <span class="fw-bold"><?= currency_format($p['price']) ?></span>
            <?php endif; ?>
          </p>
          <div class="mt-auto">
            <?php if ($p['stock'] <= 0): ?>
              <span class="badge bg-danger">Out of Stock</span>
            <?php else: ?>
              <span class="badge bg-success">In Stock: <?= $p['stock'] ?></span>
            <?php endif; ?>
            <div class="mt-2">
              <a href="<?= base_url('product.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
              <?php if ($p['stock'] > 0): ?>
                <form method="post" action="<?= base_url('cart.php') ?>" class="d-inline">
                  <input type="hidden" name="action" value="add">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <button class="btn btn-sm btn-primary">Add to Cart</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="col-12">
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

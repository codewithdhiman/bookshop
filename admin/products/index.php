<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();
ensure_product_columns($pdo);
$q = $pdo->query('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC');
$products = $q->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Products</h3>
  <a href="create.php" class="btn btn-primary">Add Product</a>
</div>
<div class="card admin-card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Book</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $p): ?>
        <?php
          $productId = (int)($p['id'] ?? 0);
          $title = $p['title'] ?? 'Untitled Book';
          $author = $p['author'] ?? 'Unknown Author';
          $categoryName = $p['category_name'] ?? 'Uncategorized';
          $cover = $p['cover_image'] ?? 'placeholder.png';
          $price = (float)($p['price'] ?? 0);
          $discountPrice = !empty($p['discount_price']) ? (float)$p['discount_price'] : null;
          $stock = (int)($p['stock'] ?? 0);
          $status = (int)($p['status'] ?? 0);
        ?>
        <tr>
          <td style="width:64px"><?= $productId ?></td>
          <td>
            <div class="d-flex align-items-center gap-3">
              <img src="<?= base_url('public/uploads/' . $cover) ?>" alt="" style="width:56px;height:78px;object-fit:cover;border-radius:6px">
              <div>
                <div class="fw-bold"><?= esc($title) ?></div>
                <div class="small text-muted"><?= esc($author) ?></div>
              </div>
            </div>
          </td>
          <td><?= esc($categoryName) ?></td>
          <td><?= currency_format($discountPrice !== null ? $discountPrice : $price) ?></td>
          <td><?= $stock ?></td>
          <td>
            <?php if ($status): ?><span class="badge" style="background:var(--success);color:#fff">Active</span><?php else: ?><span class="badge" style="background:var(--danger);color:#fff">Inactive</span><?php endif; ?>
          </td>
          <td class="text-end">
            <div class="btn-group">
              <a href="edit.php?id=<?= $productId ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <a href="delete.php?id=<?= $productId ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this product?')">Deactivate</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

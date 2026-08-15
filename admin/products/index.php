<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();
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
        <tr>
          <td style="width:64px"><?= $p['id'] ?></td>
          <td>
            <div class="d-flex align-items-center gap-3">
              <img src="<?= base_url('public/uploads/' . ($p['cover_image'] ?? 'placeholder.png')) ?>" alt="" style="width:56px;height:78px;object-fit:cover;border-radius:6px">
              <div>
                <div class="fw-bold"><?= esc($p['title']) ?></div>
                <div class="small text-muted"><?= esc($p['author']) ?></div>
              </div>
            </div>
          </td>
          <td><?= esc($p['category_name']) ?></td>
          <td><?= currency_format($p['discount_price'] ?: $p['price']) ?></td>
          <td><?= $p['stock'] ?></td>
          <td>
            <?php if ($p['status']): ?><span class="badge" style="background:var(--success);color:#fff">Active</span><?php else: ?><span class="badge" style="background:var(--danger);color:#fff">Inactive</span><?php endif; ?>
          </td>
          <td class="text-end">
            <div class="btn-group">
              <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this product?')">Deactivate</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

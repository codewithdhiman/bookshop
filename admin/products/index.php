<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

require_admin();
$pdo = getPDO();
$q = $pdo->query('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC');
$products = $q->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Products</h3>
  <a href="create.php" class="btn btn-primary">Add Product</a>
</div>
<table class="table table-striped">
  <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= esc($p['title']) ?></td>
      <td><?= esc($p['category_name']) ?></td>
      <td><?= currency_format($p['discount_price'] ?: $p['price']) ?></td>
      <td><?= $p['stock'] ?></td>
      <td><?= $p['status'] ? 'Active' : 'Inactive' ?></td>
      <td>
        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
        <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this product?')">Deactivate</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

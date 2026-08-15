<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

require_admin();
$pdo = getPDO();
$cats = $pdo->query('SELECT * FROM categories ORDER BY created_at DESC')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Categories</h3>
  <a href="create.php" class="btn btn-primary">Add Category</a>
</div>
<table class="table">
  <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($cats as $c): ?>
  <tr>
    <td><?= $c['id'] ?></td>
    <td><?= esc($c['name']) ?></td>
    <td><?= esc($c['slug']) ?></td>
    <td><?= $c['status']? 'Active':'Inactive' ?></td>
    <td>
      <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
      <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();
$cats = $pdo->query('SELECT * FROM categories ORDER BY created_at DESC')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Categories</h3>
  <a href="create.php" class="btn btn-primary">Add Category</a>
</div>
<div class="admin-card p-3">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr><th>ID</th><th>Name</th><th>Slug</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach($cats as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= esc($c['name']) ?></td>
        <td><?= esc($c['slug']) ?></td>
        <td><?php if ($c['status']): ?><span class="badge" style="background:var(--success);color:#fff">Active</span><?php else: ?><span class="badge" style="background:var(--danger);color:#fff">Inactive</span><?php endif; ?></td>
        <td class="text-end">
          <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
          <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

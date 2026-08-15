<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1'); $stmt->execute([$id]); $c = $stmt->fetch(); if (!$c) { header('Location: index.php'); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF'; }
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { $errors[]='Name required'; }
    $slug = slugify($name);
    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE categories SET name=?, slug=?, description=?, status=? WHERE id=?');
        $stmt->execute([$name, $slug, $_POST['description'] ?? null, isset($_POST['status'])?1:0, $id]);
        header('Location: index.php'); exit;
    }
}
?>
<h3 class="mb-3">Edit Category</h3>
<?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <div class="admin-card p-3">
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required value="<?= esc($c['name']) ?>"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= esc($c['description']) ?></textarea></div>
    <div class="mb-3 form-check"><input type="checkbox" name="status" class="form-check-input" id="status" <?= $c['status']?'checked':'' ?>><label for="status" class="form-check-label">Active</label></div>
    <div class="d-grid"><button class="btn btn-primary">Save</button></div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

require_admin();
$pdo = getPDO();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF'; }
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { $errors[]='Name required'; }
    $slug = slugify($name);
    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $_POST['description'] ?? null, isset($_POST['status'])?1:0]);
        header('Location: index.php'); exit;
    }
}
?>
<h3>Add Category</h3>
<?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
  <div class="mb-3 form-check"><input type="checkbox" name="status" class="form-check-input" id="status" checked><label for="status" class="form-check-label">Active</label></div>
  <button class="btn btn-primary">Create</button>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

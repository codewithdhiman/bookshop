<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

require_admin();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$product = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1'); $product->execute([$id]); $p = $product->fetch(); if (!$p) { header('Location: index.php'); exit; }
$cats = $pdo->query('SELECT * FROM categories WHERE status = 1')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF'; }
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $discount = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null;
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    if ($title === '' || !$category_id) { $errors[]='Title and category required'; }
    if ($discount !== null && $discount > $price) { $errors[]='Discount cannot exceed price'; }
    $imageName = $p['cover_image'];
    if (empty($errors) && !empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        [$fn, $err] = handle_image_upload($_FILES['cover_image']);
        if ($err) $errors[] = $err; else $imageName = $fn;
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE products SET category_id=?, title=?, isbn=?, author=?, publisher=?, description=?, price=?, discount_price=?, stock=?, cover_image=?, status=? WHERE id=?');
        $stmt->execute([$category_id, $title, $_POST['isbn'] ?? null, $_POST['author'] ?? null, $_POST['publisher'] ?? null, $_POST['description'] ?? null, $price, $discount, $stock, $imageName, isset($_POST['status'])?1:0, $id]);
        header('Location: index.php'); exit;
    }
}
?>
<h3>Edit Product</h3>
<?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?= esc($p['title']) ?>"></div>
  <div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Select</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id']==$p['category_id']?'selected':'' ?>><?= esc($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="mb-3"><label class="form-label">Author</label><input name="author" class="form-control" value="<?= esc($p['author']) ?>"></div>
  <div class="mb-3"><label class="form-label">Publisher</label><input name="publisher" class="form-control" value="<?= esc($p['publisher']) ?>"></div>
  <div class="mb-3"><label class="form-label">ISBN</label><input name="isbn" class="form-control" value="<?= esc($p['isbn']) ?>"></div>
  <div class="mb-3"><label class="form-label">Price</label><input name="price" type="number" step="0.01" class="form-control" required value="<?= esc($p['price']) ?>"></div>
  <div class="mb-3"><label class="form-label">Discount Price</label><input name="discount_price" type="number" step="0.01" class="form-control" value="<?= esc($p['discount_price']) ?>"></div>
  <div class="mb-3"><label class="form-label">Stock</label><input name="stock" type="number" class="form-control" value="<?= $p['stock'] ?>"></div>
    <div class="mb-3"><label class="form-label">Cover Image</label><input name="cover_image" type="file" accept="image/*" class="form-control"><div class="mt-2"><?php if ($p['cover_image']): ?><img src="<?= base_url('public/uploads/' . $p['cover_image']) ?>" style="height:80px"><?php endif; ?></div></div>
  <div class="mb-3 form-check"><input type="checkbox" name="status" class="form-check-input" id="status" <?= $p['status']?'checked':'' ?>><label for="status" class="form-check-label">Active</label></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"><?= esc($p['description']) ?></textarea></div>
  <button class="btn btn-primary">Save</button>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

require_admin();
$pdo = getPDO();
$cats = $pdo->query('SELECT * FROM categories WHERE status = 1')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ensure tax_rate column exists
    ensure_column_exists($pdo, 'products', 'tax_rate', 'tax_rate DECIMAL(5,2) NULL DEFAULT NULL');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF'; }
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $discount = $_POST['discount_price'] !== '' ? (float)$_POST['discount_price'] : null;
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    if ($title === '' || !$category_id) { $errors[]='Title and category required'; }
    if ($discount !== null && $discount > $price) { $errors[]='Discount cannot exceed price'; }
    $imageName = null;
    if (empty($errors) && !empty($_FILES['cover_image'])) {
        [$fn, $err] = handle_image_upload($_FILES['cover_image']);
        if ($err) $errors[] = $err; else $imageName = $fn;
    }
    if (empty($errors)) {
        $slug = slugify($title) . '-' . bin2hex(random_bytes(3));
        $tax_rate = $_POST['tax_rate'] !== '' ? (float)$_POST['tax_rate'] : null;
        $stmt = $pdo->prepare('INSERT INTO products (category_id, title, slug, isbn, author, publisher, description, price, discount_price, stock, cover_image, status, tax_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$category_id, $title, $slug, $_POST['isbn'] ?? null, $_POST['author'] ?? null, $_POST['publisher'] ?? null, $_POST['description'] ?? null, $price, $discount, $stock, $imageName, isset($_POST['status'])?1:0, $tax_rate]);
        header('Location: index.php'); exit;
    }
}
?>
<h3>Add Product</h3>
<?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Select</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="mb-3"><label class="form-label">Author</label><input name="author" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Publisher</label><input name="publisher" class="form-control"></div>
  <div class="mb-3"><label class="form-label">ISBN</label><input name="isbn" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Price</label><input name="price" type="number" step="0.01" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Discount Price</label><input name="discount_price" type="number" step="0.01" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Stock</label><input name="stock" type="number" class="form-control" value="0"></div>
    <div class="mb-3"><label class="form-label">Tax Rate (%)</label><input name="tax_rate" type="number" step="0.01" class="form-control" placeholder="Leave empty to use global tax"></div>
  <div class="mb-3"><label class="form-label">Cover Image</label><input name="cover_image" type="file" accept="image/*" class="form-control"></div>
  <div class="mb-3 form-check"><input type="checkbox" name="status" class="form-check-input" id="status" checked><label for="status" class="form-check-label">Active</label></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"></textarea></div>
  <button class="btn btn-primary">Create</button>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();
ensure_product_columns($pdo);
$cats = $pdo->query('SELECT * FROM categories WHERE status = 1')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF'; }
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $discountInput = trim((string)($_POST['discount_price'] ?? ''));
    $discount = $discountInput !== '' ? (float)$discountInput : null;
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    $author = trim((string)($_POST['author'] ?? ''));
    $isbn = null;
    try {
      $isbn = product_isbn($pdo, $_POST['isbn'] ?? null);
    } catch (InvalidArgumentException $e) {
      $errors[] = $e->getMessage();
    }
    if ($title === '' || !$category_id) { $errors[]='Title and category required'; }
    if ($discount !== null && $discount > $price) { $errors[]='Discount cannot exceed price'; }
    $imageName = null;
    if (empty($errors) && !empty($_FILES['cover_image'])) {
        [$fn, $err] = handle_image_upload($_FILES['cover_image']);
        if ($err) $errors[] = $err; else $imageName = $fn;
    }
    if (empty($errors)) {
        $slug = slugify($title) . '-' . bin2hex(random_bytes(3));
        $taxInput = trim((string)($_POST['tax_rate'] ?? ''));
        $tax_rate = $taxInput !== '' ? (float)$taxInput : null;
        $stmt = $pdo->prepare('INSERT INTO products (category_id, title, slug, isbn, author, publisher, description, price, discount_price, stock, cover_image, status, tax_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$category_id, $title, $slug, $isbn, $author, trim((string)($_POST['publisher'] ?? '')) ?: null, trim((string)($_POST['description'] ?? '')) ?: null, $price, $discount, $stock, $imageName, isset($_POST['status'])?1:0, $tax_rate]);
      // Save SEO metadata for the newly created product
      $newId = (int)$pdo->lastInsertId();
      $seoData = [
        'seo_title' => $_POST['seo_title'] ?? null,
        'seo_description' => $_POST['seo_description'] ?? null,
        'seo_keywords' => $_POST['seo_keywords'] ?? null,
        'canonical' => $_POST['canonical'] ?? null,
        'og_title' => $_POST['og_title'] ?? null,
        'og_description' => $_POST['og_description'] ?? null,
        'robots' => $_POST['robots'] ?? null,
      ];
      if (!empty($_FILES['og_image']) && $_FILES['og_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        [$fn, $err] = handle_image_upload($_FILES['og_image']);
        if ($fn) $seoData['og_image'] = $fn;
      }
      if (!empty($_FILES['twitter_image']) && $_FILES['twitter_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        [$fn, $err] = handle_image_upload($_FILES['twitter_image']);
        if ($fn) $seoData['twitter_image'] = $fn;
      }
      save_seo_meta($pdo, 'product', $newId, $seoData);
      header('Location: index.php'); exit;
    }
}
?>
<h3 class="mb-3">Add Product</h3>
<?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <div class="row">
    <div class="col-md-8">
      <div class="admin-card p-3 mb-3">
        <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Select</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">Author</label><input name="author" class="form-control"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Publisher</label><input name="publisher" class="form-control"></div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">ISBN</label><input name="isbn" class="form-control"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Stock</label><input name="stock" type="number" class="form-control" value="0"></div>
        </div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="6"></textarea></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="admin-card p-3 mb-3">
        <div class="mb-3"><label class="form-label">Price</label><input name="price" type="number" step="0.01" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Discount Price</label><input name="discount_price" type="number" step="0.01" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Tax Rate (%)</label><input name="tax_rate" type="number" step="0.01" class="form-control" placeholder="Leave empty to use global tax"></div>
        <div class="mb-3">
          <label class="form-label">Cover Image</label>
          <input name="cover_image" type="file" accept="image/*" class="form-control" data-preview="#coverPreview">
          <img id="coverPreview" src="" alt="Preview" style="display:none;margin-top:10px;width:100%;border-radius:8px;object-fit:cover">
        </div>
        <details class="mb-3">
          <summary class="form-label">SEO Settings</summary>
          <div style="margin-top:10px;">
            <div class="mb-2"><label class="form-label">SEO Title <small class="text-muted">(<= 60)</small></label>
              <input name="seo_title" class="form-control" maxlength="255" data-charcount="60"></div>
            <div class="mb-2"><label class="form-label">Meta Description <small class="text-muted">(<= 160)</small></label>
              <textarea name="seo_description" class="form-control" rows="3" maxlength="320" data-charcount="160"></textarea></div>
            <div class="mb-2"><label class="form-label">Keywords</label>
              <input name="seo_keywords" class="form-control"></div>
            <div class="mb-2"><label class="form-label">Canonical URL</label>
              <input name="canonical" class="form-control"></div>
            <div class="mb-2"><label class="form-label">OG Title</label>
              <input name="og_title" class="form-control"></div>
            <div class="mb-2"><label class="form-label">OG Description</label>
              <textarea name="og_description" class="form-control" rows="2"></textarea></div>
            <div class="mb-2"><label class="form-label">OG Image</label>
              <input name="og_image" type="file" accept="image/*" class="form-control" data-preview="#ogPreview"></div>
            <img id="ogPreview" src="" alt="OG preview" style="display:none;width:100%;margin-top:8px;border-radius:6px;object-fit:cover">
            <div class="mb-2"><label class="form-label">Twitter Image</label>
              <input name="twitter_image" type="file" accept="image/*" class="form-control" data-preview="#twPreview"></div>
            <img id="twPreview" src="" alt="Twitter preview" style="display:none;width:100%;margin-top:8px;border-radius:6px;object-fit:cover">
            <div class="mb-2"><label class="form-label">Robots</label>
              <select name="robots" class="form-select">
                <option value="">Default</option>
                <option value="noindex,nofollow">noindex,nofollow</option>
                <option value="noindex,follow">noindex,follow</option>
              </select>
            </div>
            <div class="text-muted small">Title: ~50–60 chars · Description: ~150–160 chars</div>
          </div>
        </details>
        <div class="mb-3 form-check"><input type="checkbox" name="status" class="form-check-input" id="status" checked><label for="status" class="form-check-label">Active</label></div>
        <div class="d-grid"><button class="btn btn-primary">Create</button></div>
      </div>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

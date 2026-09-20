<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();
ensure_product_columns($pdo);
ensure_product_images_table($pdo);
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$product = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1'); $product->execute([$id]); $p = $product->fetch(); if (!$p) { header('Location: index.php'); exit; }
$seo = get_seo_meta($pdo, 'product', $id);
$cats = $pdo->query('SELECT * FROM categories WHERE status = 1')->fetchAll();
$errors = [];

$titleValue = $p['title'] ?? 'Untitled Book';
$categoryIdValue = (int)($p['category_id'] ?? 0);
$authorValue = $p['author'] ?? '';
$publisherValue = $p['publisher'] ?? '';
$isbnValue = $p['isbn'] ?? '';
$descriptionValue = $p['description'] ?? '';
$priceValue = (float)($p['price'] ?? 0);
$discountValue = !empty($p['discount_price']) ? (float)$p['discount_price'] : '';
$stockValue = (int)($p['stock'] ?? 0);
$coverValue = $p['cover_image'] ?? null;
$youtubeVideoUrlValue = $p['youtube_video_url'] ?? '';
$statusValue = (int)($p['status'] ?? 0);
$featuredValue = (int)($p['is_featured'] ?? 0);
$taxValue = isset($p['tax_rate']) ? $p['tax_rate'] : '';
$productImages = get_product_images($pdo, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF'; }
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $discountInput = trim((string)($_POST['discount_price'] ?? ''));
    $discount = $discountInput !== '' ? (float)$discountInput : null;
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    $author = trim((string)($_POST['author'] ?? ''));
    $youtubeVideoUrl = trim((string)($_POST['youtube_video_url'] ?? ''));
    if ($youtubeVideoUrl !== '' && youtube_embed_url($youtubeVideoUrl) === null) {
      $errors[] = 'Enter a valid YouTube video URL.';
    }
    $isbn = null;
    try {
      $isbn = product_isbn($pdo, $_POST['isbn'] ?? null, $id);
    } catch (InvalidArgumentException $e) {
      $errors[] = $e->getMessage();
    }
    if ($title === '' || !$category_id) { $errors[]='Title and category required'; }
    if ($discount !== null && $discount > $price) { $errors[]='Discount cannot exceed price'; }
    $imageName = $coverValue;
    $galleryImages = [];
    if (empty($errors) && !empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        [$fn, $err] = handle_image_upload($_FILES['cover_image']);
        if ($err) $errors[] = $err; else $imageName = $fn;
    }
      if (empty($errors) && !empty($_FILES['gallery_images'])) {
        try {
          $galleryImages = upload_product_images($_FILES['gallery_images']);
        } catch (InvalidArgumentException $e) {
          $errors[] = $e->getMessage();
        }
      }
    if (empty($errors)) {
      $taxInput = trim((string)($_POST['tax_rate'] ?? ''));
      $tax_rate = $taxInput !== '' ? (float)$taxInput : null;
      $stmt = $pdo->prepare('UPDATE products SET category_id=?, title=?, isbn=?, author=?, publisher=?, description=?, price=?, discount_price=?, stock=?, cover_image=?, youtube_video_url=?, status=?, is_featured=?, tax_rate=? WHERE id=?');
      $stmt->execute([$category_id, $title, $isbn, $author, trim((string)($_POST['publisher'] ?? '')) ?: null, trim((string)($_POST['description'] ?? '')) ?: null, $price, $discount, $stock, $imageName, $youtubeVideoUrl !== '' ? $youtubeVideoUrl : null, isset($_POST['status'])?1:0, isset($_POST['is_featured'])?1:0, $tax_rate, $id]);
      $deleteIds = array_map('intval', (array)($_POST['delete_images'] ?? []));
      if ($deleteIds) {
        $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
        $deleteStmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ? AND id IN ($placeholders)");
        $deleteStmt->execute(array_merge([$id], $deleteIds));
      }
      $coverImageChanged = $imageName !== $coverValue && $imageName !== null;
      $newImageNames = $coverImageChanged ? array_merge([$imageName], $galleryImages) : $galleryImages;
      $newImageIds = save_product_images($pdo, $id, $newImageNames);
      $primaryImageId = (int)($_POST['primary_image_id'] ?? 0);
      if ($coverImageChanged && !empty($newImageIds)) {
        set_product_primary_image($pdo, $id, $newImageIds[0]);
      } elseif ($primaryImageId > 0) {
        set_product_primary_image($pdo, $id, $primaryImageId);
      }
      $productImages = get_product_images($pdo, $id);
      if ($productImages && !array_filter($productImages, static fn(array $image): bool => !empty($image['is_primary']))) {
        set_product_primary_image($pdo, $id, (int)$productImages[0]['id']);
      }
      // Save SEO metadata
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
      save_seo_meta($pdo, 'product', $id, $seoData);
      header('Location: index.php'); exit;
    }
}
?>
<h3 class="mb-3">Edit Product</h3>
<?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <div class="row">
    <div class="col-md-8">
      <div class="admin-card p-3 mb-3">
        <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?= esc($titleValue) ?>"></div>
        <div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Select</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$c['id']==$categoryIdValue?'selected':'' ?>><?= esc($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">Author</label><input name="author" class="form-control" value="<?= esc($authorValue) ?>"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Publisher</label><input name="publisher" class="form-control" value="<?= esc($publisherValue) ?>"></div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">ISBN</label><input name="isbn" class="form-control" value="<?= esc($isbnValue) ?>"></div>
          <div class="col-md-6 mb-3"><label class="form-label">Stock</label><input name="stock" type="number" class="form-control" value="<?= $stockValue ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="6"><?= esc($descriptionValue) ?></textarea></div>
        <div class="mb-3"><label class="form-label" for="youtubeVideoUrl">YouTube Video Link <span class="text-muted">(optional)</span></label><input name="youtube_video_url" id="youtubeVideoUrl" type="url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." value="<?= esc($youtubeVideoUrlValue) ?>" data-youtube-preview-target="#youtubePreview"><div class="form-text">The video will appear in the product image carousel.</div><div id="youtubePreview" class="youtube-preview" <?= youtube_embed_url($youtubeVideoUrlValue) ? '' : 'hidden' ?>></div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="admin-card p-3 mb-3">
        <div class="mb-3"><label class="form-label">Price</label><input name="price" type="number" step="0.01" class="form-control" required value="<?= esc((string)$priceValue) ?>"></div>
        <div class="mb-3"><label class="form-label">Discount Price</label><input name="discount_price" type="number" step="0.01" class="form-control" value="<?= esc((string)$discountValue) ?>"></div>
        <div class="mb-3"><label class="form-label">Tax Rate (%)</label><input name="tax_rate" type="number" step="0.01" class="form-control" value="<?= esc((string)$taxValue) ?>" placeholder="Leave empty to use global tax"></div>
        <div class="mb-3">
          <label class="form-label">Cover Image</label>
          <input name="cover_image" type="file" accept="image/*" class="form-control" data-preview="#coverPreview">
          <img id="coverPreview" src="<?= $coverValue ? base_url('public/uploads/' . $coverValue) : '' ?>" alt="Preview" style="display:<?= $coverValue ? 'block' : 'none' ?>;margin-top:10px;width:100%;border-radius:8px;object-fit:cover">
        </div>
        <div class="mb-3">
          <label class="form-label">Add Product Images</label>
          <input name="gallery_images[]" type="file" accept="image/jpeg,image/png,image/webp" class="form-control" multiple>
        </div>
        <?php if ($productImages): ?>
          <div class="mb-3">
            <label class="form-label">Product Gallery</label>
            <div class="product-gallery-admin">
              <?php foreach ($productImages as $image): ?>
                <label class="product-gallery-admin__item">
                  <img src="<?= base_url('public/uploads/' . $image['image']) ?>" alt="">
                  <span><input type="radio" name="primary_image_id" value="<?= (int)$image['id'] ?>" <?= !empty($image['is_primary']) ? 'checked' : '' ?>> Main</span>
                  <span><input type="checkbox" name="delete_images[]" value="<?= (int)$image['id'] ?>"> Remove</span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <details class="mb-3">
          <summary class="form-label">SEO Settings</summary>
          <div style="margin-top:10px;">
            <div class="mb-2"><label class="form-label">SEO Title <small class="text-muted">(<= 60)</small></label>
              <input name="seo_title" class="form-control" maxlength="255" data-charcount="60" value="<?= esc($seo['seo_title'] ?? '') ?>"></div>
            <div class="mb-2"><label class="form-label">Meta Description <small class="text-muted">(<= 160)</small></label>
              <textarea name="seo_description" class="form-control" rows="3" maxlength="320" data-charcount="160"><?= esc($seo['seo_description'] ?? '') ?></textarea></div>
            <div class="mb-2"><label class="form-label">Keywords</label>
              <input name="seo_keywords" class="form-control" value="<?= esc($seo['seo_keywords'] ?? '') ?>"></div>
            <div class="mb-2"><label class="form-label">Canonical URL</label>
              <input name="canonical" class="form-control" value="<?= esc($seo['canonical'] ?? '') ?>"></div>
            <div class="mb-2"><label class="form-label">OG Title</label>
              <input name="og_title" class="form-control" value="<?= esc($seo['og_title'] ?? '') ?>"></div>
            <div class="mb-2"><label class="form-label">OG Description</label>
              <textarea name="og_description" class="form-control" rows="2"><?= esc($seo['og_description'] ?? '') ?></textarea></div>
            <div class="mb-2"><label class="form-label">OG Image</label>
              <input name="og_image" type="file" accept="image/*" class="form-control" data-preview="#ogPreview"></div>
            <img id="ogPreview" src="<?= !empty($seo['og_image']) ? base_url('public/uploads/' . $seo['og_image']) : '' ?>" alt="OG preview" style="display:<?= !empty($seo['og_image']) ? 'block' : 'none' ?>;width:100%;margin-top:8px;border-radius:6px;object-fit:cover">
            <div class="mb-2"><label class="form-label">Twitter Image</label>
              <input name="twitter_image" type="file" accept="image/*" class="form-control" data-preview="#twPreview"></div>
            <img id="twPreview" src="<?= !empty($seo['twitter_image']) ? base_url('public/uploads/' . $seo['twitter_image']) : '' ?>" alt="Twitter preview" style="display:<?= !empty($seo['twitter_image']) ? 'block' : 'none' ?>;width:100%;margin-top:8px;border-radius:6px;object-fit:cover">
            <div class="mb-2"><label class="form-label">Robots</label>
              <select name="robots" class="form-select">
                <option value="" <?= empty($seo['robots']) ? 'selected' : '' ?>>Default</option>
                <option value="noindex,nofollow" <?= (isset($seo['robots']) && $seo['robots']==='noindex,nofollow') ? 'selected' : '' ?>>noindex,nofollow</option>
                <option value="noindex,follow" <?= (isset($seo['robots']) && $seo['robots']==='noindex,follow') ? 'selected' : '' ?>>noindex,follow</option>
              </select>
            </div>
            <div class="text-muted small">Title: ~50–60 chars · Description: ~150–160 chars</div>
          </div>
        </details>
        <div class="mb-3 form-check"><input type="checkbox" name="status" class="form-check-input" id="status" <?= $statusValue ? 'checked' : '' ?>><label for="status" class="form-check-label">Active</label></div>
        <div class="mb-3 form-check"><input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured" <?= $featuredValue ? 'checked' : '' ?>><label for="isFeatured" class="form-check-label">Feature on homepage</label></div>
        <div class="d-grid"><button class="btn btn-primary">Save</button></div>
      </div>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

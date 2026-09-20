<?php
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
  header('Location: ' . base_url('products.php')); exit;
}
require_once __DIR__ . '/includes/header.php';
$pdo = getPDO();
ensure_product_columns($pdo);
ensure_product_images_table($pdo);
$stmt = $pdo->prepare('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.status = 1 LIMIT 1');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { echo '<div class="alert alert-danger">Product not found.</div>'; require __DIR__ . '/includes/footer.php'; exit; }

$title = $p['title'] ?? 'Untitled Book';
$author = $p['author'] ?? 'Unknown Author';
$cover = $p['cover_image'] ?? 'placeholder.png';
$publisher = $p['publisher'] ?? '';
$description = $p['description'] ?? '';
$price = (float)($p['price'] ?? 0);
$discountPrice = !empty($p['discount_price']) ? (float)$p['discount_price'] : null;
$stock = (int)($p['stock'] ?? 0);
$productId = (int)($p['id'] ?? 0);
$youtubeEmbedUrl = youtube_embed_url($p['youtube_video_url'] ?? null);
$gallery = get_product_images($pdo, $productId);
if (!$gallery && $cover !== 'placeholder.png') {
  $gallery = [['id' => 0, 'image' => $cover, 'is_primary' => 1]];
}
$primaryGalleryImage = $gallery[0]['image'] ?? $cover;
$galleryItemCount = count($gallery) + ($youtubeEmbedUrl ? 1 : 0);

?>
<div class="row product-detail">
  <div class="col-12 col-md-5 mb-3">
    <div class="product-viewer" data-product-gallery>
      <div class="product-viewer__main" data-gallery-main tabindex="0" title="Click to zoom">
        <div class="product-viewer__slides">
          <?php foreach ($gallery as $galleryIndex => $galleryImage): ?>
            <div class="product-viewer__slide <?= $galleryIndex === 0 ? 'is-active' : '' ?>" data-gallery-slide>
              <img src="<?= base_url('public/uploads/' . $galleryImage['image']) ?>" class="img-fluid" alt="<?= esc($title) ?> image <?= $galleryIndex + 1 ?>" data-gallery-image>
            </div>
          <?php endforeach; ?>
          <?php if ($youtubeEmbedUrl): ?>
            <div class="product-viewer__slide <?= !$gallery ? 'is-active' : '' ?>" data-gallery-slide>
              <iframe class="product-viewer__video" src="<?= esc($youtubeEmbedUrl) ?>" title="<?= esc($title) ?> video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($galleryItemCount > 1): ?>
          <button type="button" class="product-viewer__arrow product-viewer__arrow--prev" data-gallery-nav="prev" aria-label="Previous product image">‹</button>
          <button type="button" class="product-viewer__arrow product-viewer__arrow--next" data-gallery-nav="next" aria-label="Next product image">›</button>
        <?php endif; ?>
        <span class="product-viewer__hint">Click image to zoom</span>
      </div>
      <?php if ($galleryItemCount > 1): ?>
        <div class="product-viewer__thumbs" role="list" aria-label="Product images">
          <?php foreach ($gallery as $galleryIndex => $galleryImage): ?>
            <button type="button" class="product-viewer__thumb <?= $galleryIndex === 0 ? 'is-active' : '' ?>" data-gallery-index="<?= $galleryIndex ?>" aria-label="View product image <?= $galleryIndex + 1 ?>">
              <img src="<?= base_url('public/uploads/' . $galleryImage['image']) ?>" alt="">
            </button>
          <?php endforeach; ?>
          <?php if ($youtubeEmbedUrl): ?>
            <button type="button" class="product-viewer__thumb product-viewer__thumb--video <?= !$gallery ? 'is-active' : '' ?>" data-gallery-index="<?= count($gallery) ?>" aria-label="View product video">Video</button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="product-viewer__controls" aria-label="Image zoom controls">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-gallery-zoom="out" aria-label="Zoom out">−</button>
        <span data-gallery-zoom-level>100%</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-gallery-zoom="in" aria-label="Zoom in">+</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-gallery-zoom="reset">Reset</button>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-7 product-info">
    <h2><?= esc($title) ?></h2>
    <p class="small muted">By <?= esc($author) ?><?= $publisher ? ' | '.esc($publisher):'' ?></p>
    <div class="mb-3">
      <?php if ($discountPrice !== null): ?>
        <div class="h4 text-danger d-inline-block me-2"><?= currency_format($discountPrice) ?></div>
        <small class="text-muted text-decoration-line-through"><?= currency_format($price) ?></small>
      <?php else: ?>
        <div class="h4 d-inline-block"><?= currency_format($price) ?></div>
      <?php endif; ?>
    </div>

    <div class="specs">
      <p><?= nl2br(esc($description)) ?></p>
    </div>

    <p class="mt-2">
      <?php if ($stock <= 0): ?>
        <span class="badge bg-danger">Out of Stock</span>
      <?php else: ?>
        <span class="badge bg-success">In Stock: <?= $stock ?></span>
      <?php endif; ?>
    </p>

    <?php if ($stock > 0): ?>
      <form method="post" action="<?= base_url('cart.php') ?>" class="row g-2 align-items-center">
        <div class="col-auto">
          <div class="qty-control d-flex align-items-center">
            <button type="button" class="btn btn-outline-secondary qty-step" data-step="-1">-</button>
            <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" class="form-control text-center" style="width:88px">
            <button type="button" class="btn btn-outline-secondary qty-step" data-step="1">+</button>
          </div>
          <input type="hidden" name="product_id" value="<?= $productId ?>">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-lg btn-primary">Add to Cart</button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>

<!-- Mobile sticky add-to-cart -->
<?php if ($stock > 0): ?>
  <div class="pdp-spacer d-md-none"></div>
  <div class="pdp-sticky d-md-none" aria-hidden="false">
    <div class="thumb"><img src="<?= base_url('public/uploads/' . $primaryGalleryImage) ?>" alt="<?= esc($title) ?>"></div>
    <div class="meta">
      <div class="title"><?= esc($title) ?></div>
      <div class="price"><?= $discountPrice !== null ? currency_format($discountPrice) : currency_format($price) ?></div>
    </div>
    <div class="actions">
      <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" class="form-control" style="width:72px">
      <button class="btn btn-primary" type="submit">Add</button>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

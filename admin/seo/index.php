<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();

// Handle global SEO settings via settings table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['global_seo'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $_SESSION['flash'][] = 'Invalid CSRF'; header('Location: index.php'); exit; }
    $data = [
        'site_title' => $_POST['site_title'] ?? '',
        'meta_description' => $_POST['meta_description'] ?? '',
        'site_url' => $_POST['site_url'] ?? '',
    ];
    set_setting($pdo, 'site_name', $data['site_title']);
    set_setting($pdo, 'site_url', $data['site_url']);
    set_setting($pdo, 'global_meta', json_encode(['title'=>$data['site_title'],'description'=>$data['meta_description']]));
    $_SESSION['flash'][] = 'Global SEO settings saved';
    header('Location: index.php'); exit;
}

$siteTitle = get_setting($pdo, 'site_name', 'Bookshop');
$siteUrl = get_setting($pdo, 'site_url', base_url());
$globalMeta = @json_decode(get_setting($pdo, 'global_meta', '{}'), true) ?: [];

// SEO dashboard stats
$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$missingTitle = (int)$pdo->query("SELECT COUNT(*) FROM products p LEFT JOIN seo_meta s ON s.entity_type='product' AND s.entity_id=p.id WHERE s.seo_title IS NULL OR s.seo_title = ''")->fetchColumn();

?>
<h3>SEO Management</h3>
<div class="row">
  <div class="col-md-8">
    <div class="admin-card p-3 mb-3">
      <h5>Global SEO</h5>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="global_seo" value="1">
        <div class="mb-3"><label class="form-label">Site SEO Title</label><input name="site_title" class="form-control" value="<?= esc($siteTitle) ?>"></div>
        <div class="mb-3"><label class="form-label">Site Meta Description</label><textarea name="meta_description" class="form-control" rows="3"><?= esc($globalMeta['description'] ?? '') ?></textarea></div>
        <div class="mb-3"><label class="form-label">Site URL</label><input name="site_url" class="form-control" value="<?= esc($siteUrl) ?>"></div>
        <div class="d-grid"><button class="btn btn-primary">Save Global SEO</button></div>
      </form>
    </div>

    <div class="admin-card p-3 mb-3">
      <h5>Pages & Entities</h5>
      <p class="small text-muted">Edit SEO for specific pages and entities.</p>
      <ul class="list-group">
        <li class="list-group-item d-flex justify-content-between align-items-center">Home <a class="btn btn-sm btn-outline-primary" href="edit.php?entity=page&slug=home">Edit</a></li>
        <li class="list-group-item d-flex justify-content-between align-items-center">Products Listing <a class="btn btn-sm btn-outline-primary" href="edit.php?entity=page&slug=products">Edit</a></li>
        <li class="list-group-item d-flex justify-content-between align-items-center">Categories Listing <a class="btn btn-sm btn-outline-primary" href="edit.php?entity=page&slug=categories">Edit</a></li>
        <li class="list-group-item d-flex justify-content-between align-items-center">Cart <a class="btn btn-sm btn-outline-primary" href="edit.php?entity=page&slug=cart">Edit</a></li>
        <li class="list-group-item d-flex justify-content-between align-items-center">Checkout <a class="btn btn-sm btn-outline-primary" href="edit.php?entity=page&slug=checkout">Edit</a></li>
      </ul>
    </div>
  </div>

  <div class="col-md-4">
    <div class="admin-card p-3 mb-3 text-center">
      <h6 class="text-muted">SEO Dashboard</h6>
      <div class="h2"><?= $totalProducts ?></div>
      <div class="small text-muted">Total Products</div>
    </div>
    <div class="admin-card p-3 mb-3 text-center">
      <h6 class="text-muted">Products missing SEO title</h6>
      <div class="h3"><?= $missingTitle ?></div>
      <a class="btn btn-sm btn-outline-primary mt-2" href="../products/index.php">View Products</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

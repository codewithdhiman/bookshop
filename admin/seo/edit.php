<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();

$entity = $_GET['entity'] ?? ($_POST['entity'] ?? 'page');
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : null);
$slug = $_GET['slug'] ?? ($_POST['slug'] ?? null);

// Map page-level entities: use entity_type 'page' with entity_id null and slug stored in canonical field
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $_SESSION['flash'][] = 'Invalid CSRF'; header('Location: index.php'); exit; }
    $data = [
        'seo_title' => $_POST['seo_title'] ?: null,
        'seo_description' => $_POST['seo_description'] ?: null,
        'seo_keywords' => $_POST['seo_keywords'] ?: null,
        'canonical' => $_POST['canonical'] ?: null,
        'og_title' => $_POST['og_title'] ?: null,
        'og_description' => $_POST['og_description'] ?: null,
        'og_image' => $_POST['og_image'] ?: null,
        'twitter_image' => $_POST['twitter_image'] ?: null,
        'robots' => $_POST['robots'] ?: null,
    ];
    if ($entity === 'page') {
        // store per-page by using canonical = slug
        // check existing by entity_type='page' and entity_id=NULL and canonical=slug
        $st = $pdo->prepare('SELECT * FROM seo_meta WHERE entity_type = ? AND entity_id IS NULL AND canonical = ? LIMIT 1');
        $st->execute(['page', $slug]);
        $exists = $st->fetch();
        if ($exists) {
            $pdo->prepare('UPDATE seo_meta SET seo_title=?,seo_description=?,seo_keywords=?,og_title=?,og_description=?,og_image=?,twitter_image=?,robots=? WHERE id=?')
                ->execute([$data['seo_title'],$data['seo_description'],$data['seo_keywords'],$data['og_title'],$data['og_description'],$data['og_image'],$data['twitter_image'],$data['robots'],$exists['id']]);
        } else {
            $pdo->prepare('INSERT INTO seo_meta (entity_type,entity_id,seo_title,seo_description,seo_keywords,canonical,og_title,og_description,og_image,twitter_image,robots) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute(['page', null, $data['seo_title'],$data['seo_description'],$data['seo_keywords'],$slug,$data['og_title'],$data['og_description'],$data['og_image'],$data['twitter_image'],$data['robots']]);
        }
        $_SESSION['flash'][] = 'Page SEO saved'; header('Location: index.php'); exit;
    } else {
        // entity like product/category with id
        save_seo_meta($pdo, $entity, $id, $data);
        $_SESSION['flash'][] = 'SEO saved'; header('Location: index.php'); exit;
    }
}

$meta = [];
if ($entity === 'page') {
    $st = $pdo->prepare('SELECT * FROM seo_meta WHERE entity_type = ? AND entity_id IS NULL AND canonical = ? LIMIT 1');
    $st->execute(['page', $slug]);
    $meta = $st->fetch() ?: [];
} else {
    $meta = get_seo_meta($pdo, $entity, $id);
}

?>
<h3>SEO Editor</h3>
<div class="admin-card p-3">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="entity" value="<?= esc($entity) ?>">
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
    <?php if ($slug): ?><input type="hidden" name="slug" value="<?= esc($slug) ?>"><?php endif; ?>
    <div class="mb-3"><label class="form-label">SEO Title</label><input name="seo_title" class="form-control" value="<?= esc($meta['seo_title'] ?? '') ?>"><div class="small text-muted">Recommended: 50–60 characters</div></div>
    <div class="mb-3"><label class="form-label">Meta Description</label><textarea name="seo_description" class="form-control" rows="3"><?= esc($meta['seo_description'] ?? '') ?></textarea><div class="small text-muted">Recommended: 140–160 characters</div></div>
    <div class="mb-3"><label class="form-label">Keywords (comma separated)</label><input name="seo_keywords" class="form-control" value="<?= esc($meta['seo_keywords'] ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label">Canonical / slug (for pages)</label><input name="canonical" class="form-control" value="<?= esc($meta['canonical'] ?? $slug ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label">OG Title</label><input name="og_title" class="form-control" value="<?= esc($meta['og_title'] ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label">OG Description</label><textarea name="og_description" class="form-control" rows="2"><?= esc($meta['og_description'] ?? '') ?></textarea></div>
    <div class="mb-3"><label class="form-label">OG Image (path)</label><input name="og_image" class="form-control" value="<?= esc($meta['og_image'] ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label">Twitter Image (path)</label><input name="twitter_image" class="form-control" value="<?= esc($meta['twitter_image'] ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label">Robots</label><input name="robots" class="form-control" value="<?= esc($meta['robots'] ?? '') ?>"><div class="small text-muted">e.g. index, follow (leave empty for default)</div></div>
    <div class="d-grid"><button class="btn btn-primary">Save SEO</button></div>
  </form>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

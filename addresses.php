<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_login();
$user = current_user();
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$stmt->execute([$user['id']]);
$addresses = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>My Addresses</h3>
  <a href="<?= base_url('address_create.php') ?>" class="btn btn-primary">Add Address</a>
</div>
<?php if (empty($addresses)): ?>
  <div class="alert alert-info">No saved addresses.</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($addresses as $a): ?>
      <div class="list-group-item">
        <div class="d-flex justify-content-between">
          <div>
            <strong><?= esc($a['name']) ?></strong> <?= $a['is_default']? '<span class="badge bg-success">Default</span>':'' ?><br>
            <?= esc($a['address_line1']) ?> <?= esc($a['address_line2']) ?><br>
            <?= esc($a['city']) ?>, <?= esc($a['state']) ?> - <?= esc($a['pincode']) ?><br>
            Phone: <?= esc($a['phone']) ?>
          </div>
          <div>
            <a href="<?= base_url('address_edit.php?id=' . $a['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            <a href="<?= base_url('address_delete.php?id=' . $a['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete address?')">Delete</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

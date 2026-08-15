<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_login();
$user = current_user();
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();
?>
<h3>My Orders</h3>
<?php if (empty($orders)): ?><div class="alert alert-info">You have no orders yet.</div><?php else: ?>
<table class="table">
  <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= esc($o['order_number']) ?></td>
        <td><?= esc($o['created_at']) ?></td>
        <td><?= currency_format($o['total_amount']) ?></td>
        <td><?= esc($o['order_status']) ?></td>
        <td><a href="order.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

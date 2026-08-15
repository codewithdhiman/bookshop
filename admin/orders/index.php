<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

require_admin();
$pdo = getPDO();

$where = '1=1';
$params = [];
if (!empty($_GET['status'])) {
    $where .= ' AND order_status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['payment_status'])) {
    $where .= ' AND payment_status = ?';
    $params[] = $_GET['payment_status'];
}

$sql = "SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE $where ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Orders</h3>
  <div>
    <form class="d-flex" method="get">
      <select name="status" class="form-select form-select-sm me-2">
        <option value="">All Status</option>
        <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= ($_GET['status'] ?? '')===$s? 'selected':''; ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="payment_status" class="form-select form-select-sm me-2">
        <option value="">All Payment</option>
        <?php foreach (['pending','paid','failed','refunded'] as $ps): ?>
          <option value="<?= $ps ?>" <?= ($_GET['payment_status'] ?? '')===$ps? 'selected':''; ?>><?= ucfirst($ps) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-secondary">Filter</button>
    </form>
  </div>
</div>
<table class="table table-striped">
  <thead><tr><th>Order#</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= esc($o['order_number']) ?></td>
        <td><?= esc($o['customer_name'] ?: $o['customer_email'] ?: 'Guest') ?></td>
        <td><?= $o['created_at'] ?></td>
        <td><?= currency_format($o['total_amount']) ?></td>
        <td><?= esc($o['payment_status']) ?></td>
        <td><?= esc($o['order_status']) ?></td>
        <td><a href="view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare('SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ? LIMIT 1');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $msg = 'Invalid CSRF'; }
  else {
    $newStatus = $_POST['order_status'] ?? $order['order_status'];
    $newPayment = $_POST['payment_status'] ?? $order['payment_status'];
    try {
      $pdo->beginTransaction();
      // if cancelling now but wasn't cancelled before, restore stock
      if ($newStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
        $itst = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
        $itst->execute([$id]);
        foreach ($itst->fetchAll() as $it) {
          if ($it['product_id']) {
            $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$it['quantity'], $it['product_id']]);
          }
        }
      }
      $upd = $pdo->prepare('UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?');
      $upd->execute([$newStatus, $newPayment, $id]);
      $pdo->commit();
      header('Location: view.php?id=' . $id); exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      $msg = 'Update failed';
    }
  }
}

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?'); $items->execute([$id]); $orderItems = $items->fetchAll();

?>
<div class="d-flex justify-content-between align-items-start mb-3">
  <h3 class="mb-0">Order <?= esc($order['order_number']) ?></h3>
  <div class="text-end">
    <div class="small text-muted">Placed: <?= $order['created_at'] ?></div>
    <div class="mt-1">
      <span class="badge" style="background:var(--accent);color:#fff">Order <?= esc(ucfirst($order['order_status'])) ?></span>
      <span class="badge ms-1" style="background:<?= $order['payment_status']==='paid'?'var(--success)':'var(--secondary)' ?>;color:#fff"><?= esc(ucfirst($order['payment_status'])) ?></span>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="admin-card p-3">
      <h5 class="mb-2">Customer</h5>
      <div class="text-muted-2"><?= esc($order['customer_name'] ?: $order['customer_email'] ?: 'Guest') ?></div>
      <div class="mt-2"><?= esc($order['shipping_phone']) ?></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="admin-card p-3">
      <h5 class="mb-2">Shipping</h5>
      <div><?= esc($order['shipping_name']) ?></div>
      <div class="text-muted small mt-1"><?= nl2br(esc($order['shipping_address'])) ?></div>
      <div class="mt-2 text-muted"><?= esc($order['shipping_city']) ?>, <?= esc($order['shipping_state']) ?> - <?= esc($order['shipping_pincode']) ?></div>
    </div>
  </div>
</div>

<div class="admin-card p-3 mb-3">
  <h5 class="mb-3">Items</h5>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-end">Total</th></tr></thead>
      <tbody>
      <?php foreach ($orderItems as $it): ?>
        <tr>
          <td><?= esc($it['product_name']) ?></td>
          <td><?= currency_format($it['price']) ?></td>
          <td><?= $it['quantity'] ?></td>
          <td class="text-end"><?= currency_format($it['total']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="admin-card p-3">
      <h5>Totals</h5>
      <div class="small text-muted">Subtotal: <?= currency_format($order['subtotal']) ?></div>
      <div class="small text-muted">Shipping: <?= currency_format($order['shipping_charge']) ?></div>
      <div class="small text-muted">Tax: <?= currency_format($order['tax_amount'] ?? 0) ?></div>
      <div class="h5 mt-2">Total: <?= currency_format($order['total_amount']) ?></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="admin-card p-3">
      <h5 class="mb-3">Update Status</h5>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Order Status</label>
            <select name="order_status" class="form-select">
              <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $order['order_status']===$s? 'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select">
              <?php foreach (['pending','paid','failed','refunded'] as $ps): ?>
                <option value="<?= $ps ?>" <?= $order['payment_status']===$ps? 'selected':'' ?>><?= ucfirst($ps) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="btn btn-primary">Save</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>

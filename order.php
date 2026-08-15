<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_login();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . base_url('my-orders.php')); exit; }
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $user['id']]);
$order = $stmt->fetch();
if (!$order) { echo '<div class="alert alert-danger">Order not found.</div>'; require __DIR__ . '/includes/footer.php'; exit; }
$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?'); $items->execute([$order['id']]); $orderItems = $items->fetchAll();
?>
<h3>Order <?= esc($order['order_number']) ?></h3>
<div class="row">
  <div class="col-md-6">
    <h5>Shipping</h5>
    <p><?= esc($order['shipping_name']) ?><br><?= esc($order['shipping_address']) ?><br><?= esc($order['shipping_city']) ?>, <?= esc($order['shipping_state']) ?> - <?= esc($order['shipping_pincode']) ?><br>Phone: <?= esc($order['shipping_phone']) ?></p>
  </div>
  <div class="col-md-6">
    <h5>Summary</h5>
    <p>Subtotal: <?= currency_format($order['subtotal']) ?><br>Shipping: <?= currency_format($order['shipping_charge']) ?><br>Tax: <?= currency_format($order['tax_amount'] ?? 0) ?><br><strong>Total: <?= currency_format($order['total_amount']) ?></strong></p>
  </div>
</div>
<h5>Items</h5>
<table class="table"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead><tbody>
<?php foreach($orderItems as $it): ?>
  <tr>
    <td><?= esc($it['product_name']) ?></td>
    <td><?= currency_format($it['price']) ?></td>
    <td><?= $it['quantity'] ?></td>
    <td><?= currency_format($it['total']) ?></td>
  </tr>
<?php endforeach; ?>
</tbody></table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

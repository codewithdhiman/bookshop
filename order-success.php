<?php
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . base_url()); exit; }
require_once __DIR__ . '/includes/header.php';
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { echo '<div class="alert alert-danger">Order not found.</div>'; require __DIR__ . '/includes/footer.php'; exit; }

?>
<div class="text-center">
  <h3>Thank you! Your order has been placed.</h3>
  <p>Order Number: <strong><?= esc($order['order_number']) ?></strong></p>
  <p>Total: <strong><?= currency_format($order['total_amount']) ?></strong></p>
  <a href="<?= base_url('products.php') ?>" class="btn btn-primary">Continue Shopping</a>
  <a href="<?= base_url() ?>" class="btn btn-outline-secondary">Home</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

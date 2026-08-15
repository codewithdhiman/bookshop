<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$cart = cart_get();
if (empty($cart)) {
  echo '<div class="alert alert-info">Cart is empty. <a href="' . base_url('products.php') . '">Shop now</a></div>'; require __DIR__ . '/includes/footer.php'; exit;
}

$pdo = getPDO();
$placeholders = implode(',', array_fill(0, count($cart), '?'));
$stmt = $pdo->prepare("SELECT id, title, price, discount_price, stock, tax_rate FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($cart));
$rows = $stmt->fetchAll();
$items = [];
$subtotal = 0;
foreach ($rows as $r) {
    $pid = $r['id'];
    $qty = $cart[$pid];
    $price = $r['discount_price'] ?: $r['price'];
    $line = $price * $qty;
    $items[] = ['product'=>$r,'quantity'=>$qty,'line'=>$line];
    $subtotal += $line;
}

$shipping = shipping_charge_for($subtotal);
// determine tax: per-product tax if set, otherwise global tax_rate
$globalTax = (float)get_setting($pdo, 'tax_rate', '0');
$taxTotal = 0.0;
foreach ($items as &$it) {
  $taxRate = $it['product']['tax_rate'] !== null ? (float)$it['product']['tax_rate'] : $globalTax;
  $it['tax'] = round($it['line'] * ($taxRate / 100), 2);
  $taxTotal += $it['tax'];
}
$grand = $subtotal + $shipping + $taxTotal;

?>
<div class="row">
  <div class="col-md-6">
    <h4>Shipping Information</h4>
    <form method="post" action="<?= base_url('place-order.php') ?>">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">City</label>
        <input name="city" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">State</label>
        <input name="state" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Pincode</label>
        <input name="pincode" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Payment Method</label>
        <select name="payment_method" class="form-select">
          <option value="cod">Cash on Delivery</option>
        </select>
      </div>
      <button class="btn btn-success">Place Order</button>
    </form>
  </div>
  <div class="col-md-6">
    <h4>Order Summary</h4>
    <ul class="list-group mb-3">
      <?php foreach ($items as $it): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= esc($it['product']['title']) ?> <span><?= $it['quantity'] ?> x <?= currency_format($it['product']['discount_price'] ?: $it['product']['price']) ?></span>
        </li>
      <?php endforeach; ?>
      <li class="list-group-item d-flex justify-content-between"><strong>Subtotal</strong><span><?= currency_format($subtotal) ?></span></li>
      <li class="list-group-item d-flex justify-content-between"><strong>Shipping</strong><span><?= currency_format($shipping) ?></span></li>
      <li class="list-group-item d-flex justify-content-between"><strong>Tax</strong><span><?= currency_format($taxTotal) ?></span></li>
      <li class="list-group-item d-flex justify-content-between"><strong>Total</strong><span><?= currency_format($grand) ?></span></li>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

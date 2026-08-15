<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? null;
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND status = 1 LIMIT 1');
    $stmt->execute([$productId]);
    $p = $stmt->fetch();
    if (!$p) { $_SESSION['flash_error']='Product not found'; header('Location: /products.php'); exit; }
    if ($qty > $p['stock']) { $_SESSION['flash_error']='Requested quantity exceeds stock'; header('Location: /product.php?id=' . $productId); exit; }
    cart_add($productId, $qty);
    header('Location: /cart.php'); exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF token'); }
    foreach ($_POST['quantities'] as $pid => $q) {
        cart_update((int)$pid, max(0, (int)$q));
    }
    header('Location: /cart.php'); exit;
}

if ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    cart_update($id, 0);
    header('Location: /cart.php'); exit;
}

$cart = cart_get();
$items = [];
$pdo = getPDO();
if ($cart) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price, discount_price, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $pid = $r['id'];
        $qty = $cart[$pid];
        $price = $r['discount_price'] ?: $r['price'];
        $items[] = [
            'product' => $r,
            'quantity' => $qty,
            'line_total' => $price * $qty
        ];
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<h3>Your Cart</h3>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= esc($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div class="alert alert-info">Your cart is empty. <a href="/products.php">Browse books</a></div>
<?php else: ?>
    <form method="post" action="/cart.php">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <table class="table">
      <thead><tr><th>Book</th><th>Price</th><th>Qty</th><th>Line</th><th></th></tr></thead>
      <tbody>
      <?php $subtotal=0; foreach ($items as $it): $p=$it['product']; $subtotal += $it['line_total']; ?>
        <tr>
          <td><?= esc($p['title']) ?></td>
          <td><?= currency_format($p['discount_price'] ?: $p['price']) ?></td>
          <td><input type="number" name="quantities[<?= $p['id'] ?>]" value="<?= $it['quantity'] ?>" min="0" max="<?= $p['stock'] ?>" style="width:80px"></td>
          <td><?= currency_format($it['line_total']) ?></td>
          <td><a href="/cart.php?action=remove&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger">Remove</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <button class="btn btn-primary">Update Cart</button>
      </div>
      <div>
        <strong>Subtotal: <?= currency_format($subtotal) ?></strong>
        <a href="/checkout.php" class="btn btn-success ms-3">Proceed to Checkout</a>
      </div>
    </div>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

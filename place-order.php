<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . base_url('checkout.php')); exit; }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die('Invalid CSRF'); }

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'cod';

if ($name === '' || $phone === '' || $address === '' || $city === '' || $state === '' || $pincode === '') {
    $_SESSION['flash_error'] = 'Please fill all required fields';
    header('Location: ' . base_url('checkout.php')); exit;
}

$cart = cart_get();
if (empty($cart)) { $_SESSION['flash_error']='Cart is empty'; header('Location: ' . base_url('products.php')); exit; }

$pdo = getPDO();
try {
    $pdo->beginTransaction();
    // load products and validate stock
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price, discount_price, stock FROM products WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute(array_keys($cart));
    $rows = $stmt->fetchAll();
    $subtotal = 0;
    $items = [];
    foreach ($rows as $r) {
        $pid = $r['id'];
        $qty = $cart[$pid] ?? 0;
        if ($qty <= 0) { throw new Exception('Invalid quantity for product ' . $pid); }
        if ($qty > $r['stock']) { throw new Exception('Insufficient stock for: ' . $r['title']); }
        $price = $r['discount_price'] ?: $r['price'];
        $line = $price * $qty;
        $items[] = ['product_id'=>$pid,'name'=>$r['title'],'price'=>$price,'quantity'=>$qty,'total'=>$line];
        $subtotal += $line;
    }

    $shipping = shipping_charge_for($subtotal);
    $total = $subtotal + $shipping;

    // generate order number
    $date = date('Ymd');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()');
    $stmt->execute();
    $countToday = (int)$stmt->fetchColumn();
    $seq = str_pad((string)($countToday + 1), 4, '0', STR_PAD_LEFT);
    $orderNumber = 'ORD-' . date('Ymd') . '-' . $seq;

    // insert order
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, order_number, subtotal, discount, shipping_charge, total_amount, payment_method, payment_status, order_status, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $userId = $_SESSION['user']['id'] ?? null;
    $stmt->execute([$userId, $orderNumber, $subtotal, 0.00, $shipping, $total, $payment_method, $payment_method === 'cod' ? 'pending' : 'pending', 'pending', $name, $phone, $address, $city, $state, $pincode]);
    $orderId = (int)$pdo->lastInsertId();

    // insert order items and reduce stock
    $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)');
    $stmtUpdate = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
    foreach ($items as $it) {
        $stmtItem->execute([$orderId, $it['product_id'], $it['name'], $it['price'], $it['quantity'], $it['total']]);
        $stmtUpdate->execute([$it['quantity'], $it['product_id']]);
    }

    $pdo->commit();
    cart_clear();
    header('Location: ' . base_url('order-success.php?id=' . $orderId));
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Order error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Something went wrong while placing your order. Please try again.';
    header('Location: ' . base_url('checkout.php')); exit;
}

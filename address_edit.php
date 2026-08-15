<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: addresses.php'); exit; }
$stmt = $pdo->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$addr = $stmt->fetch();
if (!$addr) { header('Location: addresses.php'); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[]='Invalid CSRF'; }
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address1 = trim($_POST['address_line1'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    if ($name==='' || $phone==='' || $address1==='' || $city==='' || $state==='' || $pincode==='') { $errors[]='All fields required'; }
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    if (empty($errors)) {
        if ($is_default) { $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$user['id']]); }
        $stmt = $pdo->prepare('UPDATE addresses SET name=?, phone=?, address_line1=?, address_line2=?, city=?, state=?, pincode=?, is_default=? WHERE id=? AND user_id=?');
        $stmt->execute([$name, $phone, $address1, $_POST['address_line2'] ?? null, $city, $state, $pincode, $is_default, $id, $user['id']]);
        header('Location: ' . base_url('addresses.php')); exit;
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<h3>Edit Address</h3>
<?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="<?= esc($addr['name']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= esc($addr['phone']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Address Line 1</label><input name="address_line1" class="form-control" value="<?= esc($addr['address_line1']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Address Line 2</label><input name="address_line2" class="form-control" value="<?= esc($addr['address_line2']) ?>"></div>
  <div class="mb-3"><label class="form-label">City</label><input name="city" class="form-control" value="<?= esc($addr['city']) ?>" required></div>
  <div class="mb-3"><label class="form-label">State</label><input name="state" class="form-control" value="<?= esc($addr['state']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Pincode</label><input name="pincode" class="form-control" value="<?= esc($addr['pincode']) ?>" required></div>
  <div class="mb-3 form-check"><input type="checkbox" name="is_default" class="form-check-input" id="is_default" <?= $addr['is_default']? 'checked':'' ?>><label for="is_default" class="form-check-label">Set as default</label></div>
  <button class="btn btn-primary">Save</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

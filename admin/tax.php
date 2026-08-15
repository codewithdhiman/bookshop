<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

require_admin();
$pdo = getPDO();
// Simple tax management stored in a settings table (create if missing)
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) UNIQUE,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Use shared get_setting() from includes/functions.php (do not redeclare here)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tax = floatval($_POST['tax_rate'] ?? 0);
  $st = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
  $st->execute(['tax_rate', (string)$tax]);
  header('Location: ' . base_url('admin/tax.php')); exit;
}
$tax_rate = (float)get_setting($pdo, 'tax_rate', '0');
?>
<h3>Tax Management</h3>
<form method="post" class="mb-3">
  <div class="mb-3"><label class="form-label">Global Tax Rate (%)</label>
    <input name="tax_rate" type="number" step="0.01" class="form-control" value="<?= esc($tax_rate) ?>">
  </div>
  <button class="btn btn-primary">Save</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

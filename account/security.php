<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/twofactor.php';
$pdo = getPDO();
require_login();
$user = current_user();
$errors = [];

// fetch fresh user data
$st = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1'); $st->execute([$user['id']]); $u = $st->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF'; }
    if (!empty($_POST['action']) && $_POST['action'] === 'regenerate') {
        // regenerate recovery codes
        $codes = tf_generate_recovery_codes(8);
        $pdo->prepare('DELETE FROM user_recovery_codes WHERE user_id = ?')->execute([$u['id']]);
        $ins = $pdo->prepare('INSERT INTO user_recovery_codes (user_id, code_hash) VALUES (?,?)');
        foreach ($codes as $c) { $ins->execute([$u['id'], tf_hash_recovery_code($c)]); }
        $_SESSION['twofactor_recovery_codes'] = $codes;
        header('Location: ' . base_url('recovery-codes.php')); exit;
    }
    if (!empty($_POST['action']) && $_POST['action'] === 'disable') {
        $password = $_POST['password'] ?? '';
        $code = $_POST['code'] ?? '';
        // require password
        $st = $pdo->prepare('SELECT password FROM users WHERE id = ?'); $st->execute([$u['id']]); $pw = $st->fetchColumn();
        if (!password_verify($password, $pw)) { $errors[] = 'Invalid password'; }
        if (!tf_verify_totp($u['two_factor_secret'], $code, 1)) { $errors[] = 'Invalid TOTP code'; }
        if (empty($errors)) {
            $pdo->prepare('UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL, two_factor_verified_at = NULL WHERE id = ?')->execute([$u['id']]);
            $pdo->prepare('DELETE FROM user_recovery_codes WHERE user_id = ?')->execute([$u['id']]);
            $_SESSION['flash_success'] = 'Two-factor authentication disabled.';
            header('Location: ' . base_url('account/security.php')); exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row">
  <div class="col-md-8 offset-md-2">
    <h3>Security</h3>
    <div class="card p-3 mb-3">
      <h5>Two-Factor Authentication</h5>
      <p>Status: <strong><?= $u['two_factor_enabled']? 'Enabled':'Disabled' ?></strong></p>
      <?php if (!$u['two_factor_enabled']): ?>
        <p>Secure your account with an authenticator app.</p>
        <a href="<?= base_url('setup-2fa.php') ?>" class="btn btn-primary">Enable Two-Factor</a>
      <?php else: ?>
        <p>Manage your 2FA settings below. Regenerating recovery codes will invalidate old codes.</p>
        <form method="post" class="mb-2">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="regenerate">
          <button class="btn btn-outline-primary">Regenerate Recovery Codes</button>
        </form>
        <hr>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="disable">
          <div class="mb-2"><label class="form-label">Current password</label><input name="password" type="password" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Current TOTP code</label><input name="code" class="form-control" inputmode="numeric" required></div>
          <button class="btn btn-danger">Disable Two-Factor</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/twofactor.php';

$pdo = getPDO();
$errors = [];

// Get the pending email from session
$email = $_SESSION['twofactor_setup_email'] ?? null;
if (!$email) { header('Location: ' . base_url('register.php')); exit; }

// Find user
$st = $pdo->prepare('SELECT id, email, two_factor_enabled FROM users WHERE email = ? LIMIT 1');
$st->execute([$email]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: ' . base_url('register.php')); exit; }

// If secret already exists and enabled, skip
if (!empty($user['two_factor_enabled'])) {
    unset($_SESSION['twofactor_setup_email']);
    $_SESSION['flash_success'] = 'Two-factor already enabled for your account.';
    header('Location: ' . base_url('login.php'));
    exit;
}

// On first visit, generate a secret and store in session temporarily
if (empty($_SESSION['twofactor_secret'])) {
    $_SESSION['twofactor_secret'] = tf_generate_secret(16);
}
$secret = $_SESSION['twofactor_secret'];
$uri = tf_provisioning_uri('Bookshop', $user['email'], $secret, 'Bookshop');
// Generate QR image locally. This will throw if no local QR library is installed.
try {
  $qr = tf_qr_image_src($uri, 260);
  $qr_error = null;
} catch (Exception $e) {
  $qr = '';
  $qr_error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token'; }
    $code = trim($_POST['code'] ?? '');
    if (empty($code)) { $errors[] = 'Enter the 6-digit code from your authenticator app.'; }
    if (empty($errors)) {
        if (tf_verify_totp($secret, $code, 1)) {
            // save secret and enable 2FA
            $st = $pdo->prepare('UPDATE users SET two_factor_enabled = 1, two_factor_secret = ?, two_factor_verified_at = NOW() WHERE id = ?');
            $st->execute([$secret, $user['id']]);
            // Generate recovery codes and store hashes
            $codes = tf_generate_recovery_codes(8);
            $ins = $pdo->prepare('INSERT INTO user_recovery_codes (user_id, code_hash) VALUES (?, ?)');
            foreach ($codes as $c) { $ins->execute([$user['id'], tf_hash_recovery_code($c)]); }
            // Clear session temporary secret
            unset($_SESSION['twofactor_secret']);
            unset($_SESSION['twofactor_setup_email']);
            // Show recovery codes once: store in session for display on next page
            $_SESSION['twofactor_recovery_codes'] = $codes;
            header('Location: ' . base_url('recovery-codes.php'));
            exit;
        } else {
            $errors[] = 'Invalid code. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row">
  <div class="col-md-6 offset-md-3">
    <h3>Secure your account with Two-Factor Authentication</h3>
    <?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
    <div class="card p-3 mb-3 text-center">
      <div class="mb-2">Scan this QR code in your authenticator app</div>
      <?php if (!empty($qr)): ?>
        <img id="tf-qr-img" src="<?= esc($qr) ?>" alt="QR code for 2FA setup" style="max-width:260px; margin:auto;" />
      <?php else: ?>
        <div class="text-danger mb-2">Unable to generate QR code locally.</div>
        <?php if (!empty($qr_error)): ?>
          <div class="small text-muted mb-2"><?= esc($qr_error) ?></div>
          <div class="small text-muted mb-2">Install the QR library with Composer, e.g.: <code>composer require endroid/qr-code</code></div>
        <?php endif; ?>
      <?php endif; ?>
      <div id="tf-qr-fallback" style="display:none">
        <div class="small text-muted mb-1">QR could not be displayed. Use this URI or manual key in your authenticator app:</div>
        <div class="small text-break"><code><?= esc($uri) ?></code></div>
        <div class="mt-2 small text-muted">Or, enter this key manually:</div>
        <div class="h6 mt-1" id="tf-secret"><?= esc($secret) ?></div>
        <button class="btn btn-sm btn-outline-secondary mt-2" onclick="(async()=>{navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('tf-secret').innerText);this.innerText='Copied';})();">Copy key</button>
      </div>
    </div>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3"><label class="form-label">Enter the 6-digit code from your authenticator app</label>
        <input name="code" class="form-control" inputmode="numeric" pattern="\d{6}" required>
      </div>
      <button class="btn btn-primary">Verify & Enable 2FA</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

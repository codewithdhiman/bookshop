<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/twofactor.php';

$pdo = getPDO();
$errors = [];

// Ensure we have a pre-auth user
if (empty($_SESSION['pre_2fa_user_id'])) {
    header('Location: ' . base_url('login.php'));
    exit;
}
$user_id = (int)$_SESSION['pre_2fa_user_id'];

// Rate limiting
$_SESSION['pre_2fa_attempts'] = $_SESSION['pre_2fa_attempts'] ?? 0;
$_SESSION['pre_2fa_block_until'] = $_SESSION['pre_2fa_block_until'] ?? 0;
if (time() < ($_SESSION['pre_2fa_block_until'] ?? 0)) {
    $errors[] = 'Too many attempts. Please wait before trying again.';
}

$st = $pdo->prepare('SELECT id, name, email, two_factor_secret FROM users WHERE id = ? LIMIT 1');
$st->execute([$user_id]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: ' . base_url('login.php')); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $errors[] = 'Invalid CSRF token'; }
    $code = trim($_POST['code'] ?? '');
    $recovery = trim($_POST['recovery_code'] ?? '');
    if ($code !== '') {
        if (tf_verify_totp($user['two_factor_secret'], $code, 1)) {
            // success: complete login
            session_regenerate_id(true);
            $_SESSION['user'] = [ 'id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'] ?? 'customer' ];
            // clear pre-2fa
            unset($_SESSION['pre_2fa_user_id']); unset($_SESSION['pre_2fa_time']); unset($_SESSION['pre_2fa_attempts']); unset($_SESSION['pre_2fa_block_until']);
            header('Location: ' . base_url()); exit;
        } else {
            $_SESSION['pre_2fa_attempts']++;
            if ($_SESSION['pre_2fa_attempts'] >= 5) {
                $_SESSION['pre_2fa_block_until'] = time() + 300; // block 5 minutes
            }
            $errors[] = 'Invalid code. Please try again.';
        }
    } elseif ($recovery !== '') {
        // verify recovery code
        $match = tf_verify_recovery_code($pdo, $user['id'], $recovery);
        if ($match !== false) {
            tf_mark_recovery_code_used($pdo, $match);
            session_regenerate_id(true);
            $_SESSION['user'] = [ 'id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'] ?? 'customer' ];
            unset($_SESSION['pre_2fa_user_id']); unset($_SESSION['pre_2fa_time']); unset($_SESSION['pre_2fa_attempts']); unset($_SESSION['pre_2fa_block_until']);
            header('Location: ' . base_url()); exit;
        } else {
            $_SESSION['pre_2fa_attempts']++;
            if ($_SESSION['pre_2fa_attempts'] >= 5) { $_SESSION['pre_2fa_block_until'] = time() + 300; }
            $errors[] = 'Invalid recovery code.';
        }
    } else {
        $errors[] = 'Enter the code from your authenticator app or a recovery code.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="row">
  <div class="col-md-6 offset-md-3">
    <h3>Two-Factor Authentication</h3>
    <?php foreach ($errors as $e): ?> <div class="alert alert-danger"><?= esc($e) ?></div> <?php endforeach; ?>
    <div class="card p-3 mb-3">
      <p>Enter the 6-digit code from your authenticator app.</p>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-3"><input name="code" class="form-control" inputmode="numeric" pattern="\d{6}" placeholder="123456"></div>
        <button class="btn btn-primary">Verify & Login</button>
      </form>
      <hr>
      <p class="small text-muted">Or use a recovery code</p>
      <form method="post" class="mt-2">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-2"><input name="recovery_code" class="form-control" placeholder="ABCD-1234"></div>
        <button class="btn btn-outline-secondary">Use Recovery Code</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

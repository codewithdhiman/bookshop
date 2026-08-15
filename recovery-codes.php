<?php
require_once __DIR__ . '/includes/auth.php';

if (empty($_SESSION['twofactor_recovery_codes'])) {
    header('Location: ' . base_url('login.php'));
    exit;
}
$codes = $_SESSION['twofactor_recovery_codes'];
unset($_SESSION['twofactor_recovery_codes']);

require_once __DIR__ . '/includes/header.php';
?>
<div class="row">
  <div class="col-md-6 offset-md-3">
    <h3>Your recovery codes</h3>
    <div class="alert alert-warning">Save these recovery codes in a safe place. Each code can be used once to access your account if you lose access to your authenticator app.</div>
    <div class="card p-3">
      <ul class="list-unstyled mb-3">
        <?php foreach ($codes as $c): ?>
          <li class="py-2"><strong><?= esc($c) ?></strong></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex gap-2">
        <a href="<?= base_url('login.php') ?>" class="btn btn-primary">Continue to Login</a>
        <button class="btn btn-outline-secondary" onclick="window.print()">Print Codes</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . base_url('admin/users.php')); exit; }
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { $_SESSION['flash_error'] = 'Invalid CSRF token'; header('Location: ' . base_url('admin/users.php')); exit; }

$action = $_POST['action'] ?? '';
$target = (int)($_POST['user_id'] ?? 0);
$me = $_SESSION['user']['id'] ?? null;

// helper: count active admins
$cntSt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 1");
$activeAdmins = (int)$cntSt->fetchColumn();

function audit(PDO $pdo, $adminId, $action, $targetId) {
    try { $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED, action VARCHAR(100), target_user_id BIGINT UNSIGNED, ip VARCHAR(45), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Exception $e) {}
    $log = $pdo->prepare('INSERT INTO admin_audit (admin_id,action,target_user_id,ip) VALUES (?,?,?,?)');
    $log->execute([$adminId,$action,$targetId,$_SERVER['REMOTE_ADDR'] ?? null]);
}

if ($action === 'activate' || $action === 'deactivate') {
    if ($target === $me && $action === 'deactivate') {
        $_SESSION['flash_error'] = 'You cannot deactivate your own account.'; header('Location: ' . base_url('admin/users.php')); exit;
    }
    // if deactivating an admin, ensure not last
    $st = $pdo->prepare('SELECT role,status FROM users WHERE id = ? LIMIT 1'); $st->execute([$target]); $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u) { $_SESSION['flash_error'] = 'User not found'; header('Location: ' . base_url('admin/users.php')); exit; }
    if ($u['role'] === 'admin' && $u['status'] == 1 && $action === 'deactivate' && $activeAdmins <= 1) {
        $_SESSION['flash_error'] = 'Cannot deactivate the last active administrator.'; header('Location: ' . base_url('admin/users.php')); exit;
    }
    $newStatus = $action === 'activate' ? 1 : 0;
    $upd = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?'); $upd->execute([$newStatus,$target]);
    audit($pdo,$me,($action==='activate'?'USER_ACTIVATED':'USER_DEACTIVATED'),$target);
    $_SESSION['flash_success'] = 'User ' . ($newStatus? 'activated' : 'deactivated') . '.';
    header('Location: ' . base_url('admin/users.php')); exit;
}

if ($action === 'delete') {
    // check orders
    $ost = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?'); $ost->execute([$target]); $orders = (int)$ost->fetchColumn();
    $st = $pdo->prepare('SELECT role,status FROM users WHERE id = ? LIMIT 1'); $st->execute([$target]); $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u) { $_SESSION['flash_error'] = 'User not found'; header('Location: ' . base_url('admin/users.php')); exit; }
    if ($u['role'] === 'admin' && $u['status'] == 1 && $activeAdmins <= 1) {
        $_SESSION['flash_error'] = 'Cannot delete the last active administrator.'; header('Location: ' . base_url('admin/users.php')); exit;
    }
    if ($orders > 0) {
        // prefer deactivate
        $pdo->prepare('UPDATE users SET status = 0 WHERE id = ?')->execute([$target]);
        audit($pdo,$me,'USER_DEACTIVATED_DUE_TO_ORDERS',$target);
        $_SESSION['flash_success'] = 'User has orders and was deactivated instead of deletion.';
        header('Location: ' . base_url('admin/users.php')); exit;
    }
    // safe to delete
    $del = $pdo->prepare('DELETE FROM users WHERE id = ?'); $del->execute([$target]);
    audit($pdo,$me,'USER_DELETED',$target);
    $_SESSION['flash_success'] = 'User deleted.';
    header('Location: ' . base_url('admin/users.php')); exit;
}

if ($action === 'reset_2fa') {
    // reset two-factor for a user
    if ($target === $me) { $_SESSION['flash_error'] = 'You cannot reset your own 2FA here.'; header('Location: ' . base_url('admin/users.php')); exit; }
    // invalidate secret and recovery codes
    $pdo->prepare('UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL, two_factor_verified_at = NULL WHERE id = ?')->execute([$target]);
    $pdo->prepare('DELETE FROM user_recovery_codes WHERE user_id = ?')->execute([$target]);
    audit($pdo,$me,'USER_2FA_RESET',$target);
    $_SESSION['flash_success'] = 'User 2FA reset.';
    header('Location: ' . base_url('admin/users.php')); exit;
}

// fallback
header('Location: ' . base_url('admin/users.php'));
exit;

?>

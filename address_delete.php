<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = current_user();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
}
header('Location: ' . base_url('addresses.php'));
exit;

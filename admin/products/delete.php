<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_admin();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare('UPDATE products SET status = 0 WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
}
header('Location: index.php'); exit;

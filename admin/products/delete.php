<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_admin();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // soft deactivate
    $stmt = $pdo->prepare('UPDATE products SET status = 0 WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: index.php'); exit;

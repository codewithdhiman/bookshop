<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_admin();
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // ensure no dependent products
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?'); $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }
}
header('Location: index.php'); exit;

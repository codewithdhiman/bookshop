<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';

require_admin();

$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT is_active
    FROM attributes
    WHERE id = ?
");

$stmt->execute([$id]);

$attribute = $stmt->fetch();

if (!$attribute) {
    header('Location: index.php');
    exit;
}

$newStatus = $attribute['is_active'] ? 0 : 1;

$stmt = $pdo->prepare("
    UPDATE attributes
    SET is_active = ?
    WHERE id = ?
");

$stmt->execute([
    $newStatus,
    $id
]);

header('Location: index.php');
exit;
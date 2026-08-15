<?php
// includes/functions.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Output escape
function esc($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// CSRF token
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Simple slug
function slugify($text): string
{
    $text = preg_replace('~[^\\pL0-9_]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

// Cart operations (stored in session)
function cart_get(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_save(array $cart)
{
    $_SESSION['cart'] = $cart;
}

function cart_add(int $productId, int $qty = 1)
{
    $cart = cart_get();
    if (isset($cart[$productId])) {
        $cart[$productId] += $qty;
    } else {
        $cart[$productId] = $qty;
    }
    cart_save($cart);
}

function cart_update(int $productId, int $qty)
{
    $cart = cart_get();
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $qty;
    }
    cart_save($cart);
}

function cart_clear()
{
    unset($_SESSION['cart']);
}

function currency_format($num): string
{
    return '₹' . number_format((float)$num, 2);
}

// Shipping rules
define('FREE_SHIPPING_THRESHOLD', 999.00);
define('SHIPPING_CHARGE', 50.00);

function shipping_charge_for(float $subtotal): float
{
    return $subtotal >= FREE_SHIPPING_THRESHOLD ? 0.00 : SHIPPING_CHARGE;
}

// Settings helper
function get_setting(PDO $pdo, string $key, $default = null)
{
    try {
        $st = $pdo->prepare('SELECT value FROM settings WHERE `key` = ? LIMIT 1');
        $st->execute([$key]);
        $v = $st->fetchColumn();
        return $v === false ? $default : $v;
    } catch (Exception $e) {
        // try to create settings table if missing
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (id INT AUTO_INCREMENT PRIMARY KEY, `key` VARCHAR(100) UNIQUE, `value` TEXT)");
        return $default;
    }
}

// Ensure a column exists on a table (safe-add)
function ensure_column_exists(PDO $pdo, string $table, string $column, string $definition)
{
    // validate table and column names to avoid SQL injection via identifiers
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        throw new InvalidArgumentException('Invalid table or column name');
    }

    // MySQL does not accept parameter placeholders for identifier positions in SHOW queries
    // so safely quote the LIKE value and run a simple query instead of a prepared statement.
    $sql = "SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column);
    $q = $pdo->query($sql);
    $exists = $q ? $q->fetch() : false;
    if (!$exists) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

// Image upload helper
function handle_image_upload(array $file, string $targetDir = __DIR__ . '/../public/uploads'): array
{
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload error'];
    }
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        return [null, 'File too large (max 2MB)'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return [null, 'Invalid file type'];
    }
    $ext = $allowed[$mime];
    if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }
    $base = bin2hex(random_bytes(8));
    $filename = $base . '.' . $ext;
    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [null, 'Failed to move uploaded file'];
    }
    return [$filename, null];
}

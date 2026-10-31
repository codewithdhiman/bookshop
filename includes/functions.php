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

function sanitize_product_description(?string $html): string
{
    $html = (string)$html;
    if ($html === '') {
        return '';
    }

    if (strip_tags($html) === $html) {
        $html = '<p>' . nl2br(esc($html), false) . '</p>';
    }

    $previousErrorSetting = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->loadHTML('<?xml encoding="UTF-8"><div id="product-description-root">' . $html . '</div>');
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrorSetting);

    $root = (new DOMXPath($document))->query('//*[@id="product-description-root"]')->item(0);
    if (!$root) {
        return '';
    }

    $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'h1', 'h2', 'h3', 'blockquote', 'ol', 'ul', 'li', 'a', 'span', 'pre'];
    $dropTags = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template'];
    $sanitizeChildren = function (DOMNode $parent) use (&$sanitizeChildren, $allowedTags, $dropTags): void {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMComment) {
                $parent->removeChild($node);
                continue;
            }
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (in_array($tag, $dropTags, true)) {
                $parent->removeChild($node);
                continue;
            }

            $sanitizeChildren($node);
            if (!in_array($tag, $allowedTags, true)) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            $attributes = [];
            foreach ($node->attributes as $attribute) {
                $attributes[] = $attribute->name;
            }
            foreach ($attributes as $attributeName) {
                $value = trim($node->getAttribute($attributeName));
                $keep = false;
                if ($attributeName === 'class') {
                    $classes = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
                    $classes = array_values(array_filter($classes, static function (string $class): bool {
                        return $class === 'ql-ui' || preg_match('/^(ql-align-(center|right|justify)|ql-size-(small|large|huge)|ql-indent-[1-8]|ql-direction-rtl|ql-font-(serif|monospace))$/', $class) === 1;
                    }));
                    if ($classes) {
                        $node->setAttribute('class', implode(' ', $classes));
                        $keep = true;
                    }
                } elseif ($attributeName === 'data-list' && $tag === 'li' && in_array($value, ['ordered', 'bullet'], true)) {
                    $keep = true;
                } elseif ($attributeName === 'style') {
                    $safeStyles = [];
                    foreach (explode(';', $value) as $declaration) {
                        $parts = explode(':', $declaration, 2);
                        if (count($parts) !== 2) {
                            continue;
                        }
                        $property = strtolower(trim($parts[0]));
                        $styleValue = trim($parts[1]);
                        if (in_array($property, ['color', 'background-color'], true)
                            && preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9.,%\s]+\)|[a-z]{3,20})$/i', $styleValue) === 1) {
                            $safeStyles[] = $property . ': ' . $styleValue;
                        } elseif ($property === 'text-align' && in_array(strtolower($styleValue), ['left', 'center', 'right', 'justify'], true)) {
                            $safeStyles[] = $property . ': ' . strtolower($styleValue);
                        }
                    }
                    if ($safeStyles) {
                        $node->setAttribute('style', implode('; ', $safeStyles));
                        $keep = true;
                    }
                } elseif ($tag === 'a' && $attributeName === 'href') {
                    $scheme = strtolower((string)parse_url($value, PHP_URL_SCHEME));
                    if (substr($value, 0, 2) !== '//' && ($scheme === '' || in_array($scheme, ['http', 'https', 'mailto', 'tel'], true))) {
                        $keep = true;
                    }
                } elseif ($tag === 'a' && $attributeName === 'target' && $value === '_blank') {
                    $keep = true;
                }

                if (!$keep) {
                    $node->removeAttribute($attributeName);
                }
            }

            if ($tag === 'a' && $node->getAttribute('target') === '_blank' && $node->hasAttribute('href')) {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
    };
    $sanitizeChildren($root);

    $result = '';
    foreach ($root->childNodes as $child) {
        $result .= $document->saveHTML($child);
    }
    return $result;
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

function product_url(string $slug): string
{
    return base_url('product/' . rawurlencode($slug));
}

function absolute_url(string $path, string $siteUrl = ''): string
{
    $path = '/' . ltrim($path, '/');
    $siteUrl = rtrim(trim($siteUrl), '/');
    if (preg_match('~^https?://~i', $siteUrl)) {
        $sitePath = rtrim((string)parse_url($siteUrl, PHP_URL_PATH), '/');
        $appPath = rtrim(BASE_URL, '/');
        if ($sitePath !== '' && $sitePath === $appPath && str_starts_with($path, $appPath . '/')) {
            $path = substr($path, strlen($appPath));
        }
        return $siteUrl . '/' . ltrim($path, '/');
    }

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^[a-z0-9.-]+(?::\d+)?$/i', $host)) {
        return $path;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host . $path;
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

// Set or update a setting
function set_setting(PDO $pdo, string $key, $value): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (id INT AUTO_INCREMENT PRIMARY KEY, `key` VARCHAR(100) UNIQUE, `value` TEXT)");
    $st = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    $st->execute([$key, (string)$value]);
}

// Ensure SEO table exists
function ensure_seo_table(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS seo_meta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT DEFAULT NULL,
        seo_title VARCHAR(255) DEFAULT NULL,
        seo_description TEXT DEFAULT NULL,
        seo_keywords TEXT DEFAULT NULL,
        canonical VARCHAR(255) DEFAULT NULL,
        og_title VARCHAR(255) DEFAULT NULL,
        og_description TEXT DEFAULT NULL,
        og_image VARCHAR(255) DEFAULT NULL,
        twitter_image VARCHAR(255) DEFAULT NULL,
        robots VARCHAR(100) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY ux_entity (entity_type, entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Fetch SEO metadata for an entity (entity_type like 'product','category','page')
function get_seo_meta(PDO $pdo, string $entity_type, ?int $entity_id = null): array
{
    ensure_seo_table($pdo);
    $st = $pdo->prepare('SELECT * FROM seo_meta WHERE entity_type = ? AND ' . ($entity_id === null ? 'entity_id IS NULL' : 'entity_id = ?') . ' LIMIT 1');
    if ($entity_id === null) $st->execute([$entity_type]); else $st->execute([$entity_type, $entity_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: [];
}

// Save SEO metadata
function save_seo_meta(PDO $pdo, string $entity_type, ?int $entity_id, array $data): void
{
    ensure_seo_table($pdo);
    $existing = get_seo_meta($pdo, $entity_type, $entity_id);
    if ($existing) {
        $sql = 'UPDATE seo_meta SET seo_title = ?, seo_description = ?, seo_keywords = ?, canonical = ?, og_title = ?, og_description = ?, og_image = ?, twitter_image = ?, robots = ? WHERE id = ?';
        $st = $pdo->prepare($sql);
        $st->execute([
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['seo_keywords'] ?? null,
            $data['canonical'] ?? null,
            $data['og_title'] ?? null,
            $data['og_description'] ?? null,
            $data['og_image'] ?? null,
            $data['twitter_image'] ?? null,
            $data['robots'] ?? null,
            $existing['id']
        ]);
    } else {
        $sql = 'INSERT INTO seo_meta (entity_type, entity_id, seo_title, seo_description, seo_keywords, canonical, og_title, og_description, og_image, twitter_image, robots) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
        $st = $pdo->prepare($sql);
        $st->execute([
            $entity_type,
            $entity_id,
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['seo_keywords'] ?? null,
            $data['canonical'] ?? null,
            $data['og_title'] ?? null,
            $data['og_description'] ?? null,
            $data['og_image'] ?? null,
            $data['twitter_image'] ?? null,
            $data['robots'] ?? null,
        ]);
    }
}

// Render SEO meta tags for the current request, with sensible fallbacks
function render_seo_meta(PDO $pdo): void
{
    $siteName = get_setting($pdo, 'site_name', 'Bookshop');
    $siteUrl = (string)get_setting($pdo, 'site_url', '');

    $script = basename($_SERVER['SCRIPT_NAME']);

    $meta = [];
    // Default global meta
    $global = get_setting($pdo, 'global_meta', null);
    if ($global && is_string($global)) {
        // try json decode
        $g = @json_decode($global, true);
        if (is_array($g)) {
            $meta = array_merge($meta, $g);
        }
    }

    // Page-specific
    if ($script === 'product.php' && (!empty($_GET['slug']) || !empty($_GET['id']))) {
        $slug = trim((string)($_GET['slug'] ?? ''));
        $id = (int)($_GET['id'] ?? 0);
        if ($slug !== '') {
            $p = $pdo->prepare('SELECT * FROM products WHERE slug = ? LIMIT 1');
            $p->execute([$slug]);
        } else {
            $p = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
            $p->execute([$id]);
        }
        $prod = $p->fetch();
        if ($prod) {
            $seo = get_seo_meta($pdo, 'product', (int)$prod['id']);
            $meta['title'] = $seo['seo_title'] ?? ($prod['title'] . ' | ' . $siteName);
            $meta['description'] = $seo['seo_description'] ?? substr(strip_tags($prod['description'] ?? ''), 0, 160);
            $meta['og_image'] = $seo['og_image'] ?? ($prod['cover_image'] ? base_url('public/uploads/' . $prod['cover_image']) : null);
            $meta['url'] = $seo['canonical'] ?: absolute_url(product_url((string)$prod['slug']), $siteUrl);
            // JSON-LD Product
            echo "<meta name=\"description\" content=\"" . esc($meta['description']) . "\">\n";
            echo "<link rel=\"canonical\" href=\"" . esc($meta['url']) . "\">\n";
            echo "<meta property=\"og:title\" content=\"" . esc($meta['title']) . "\">\n";
            echo "<meta property=\"og:description\" content=\"" . esc($meta['description']) . "\">\n";
            if (!empty($meta['og_image'])) echo "<meta property=\"og:image\" content=\"" . esc($meta['og_image']) . "\">\n";
            echo "<meta property=\"og:type\" content=\"product\">\n";
            echo "<meta property=\"og:site_name\" content=\"" . esc($siteName) . "\">\n";
            echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
            echo "<meta name=\"twitter:title\" content=\"" . esc($meta['title']) . "\">\n";
            echo "<meta name=\"twitter:description\" content=\"" . esc($meta['description']) . "\">\n";
            if (!empty($meta['og_image'])) echo "<meta name=\"twitter:image\" content=\"" . esc($meta['og_image']) . "\">\n";

            // Structured data
            $ld = [
                '@context' => 'https://schema.org/',
                '@type' => 'Product',
                'name' => $prod['title'],
                'image' => !empty($meta['og_image']) ? [$meta['og_image']] : [],
                'description' => $meta['description'],
                'sku' => $prod['isbn'] ?? null,
                'brand' => ['@type'=>'Brand','name'=>$prod['publisher'] ?? null],
                'offers' => ['@type'=>'Offer','price'=> (string)($prod['discount_price'] ?: $prod['price']),'priceCurrency'=>'INR','availability' => ($prod['stock']>0? 'https://schema.org/InStock':'https://schema.org/OutOfStock')]
            ];
            echo "<script type=\"application/ld+json\">" . json_encode($ld, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "</script>\n";
            return;
        }
    }

    // Fallbacks: simple global meta
    if (!empty($meta['title'])) echo "<title>" . esc($meta['title']) . "</title>\n";
    if (!empty($meta['description'])) echo "<meta name=\"description\" content=\"" . esc($meta['description']) . "\">\n";
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

function ensure_product_columns(PDO $pdo): void
{
    ensure_column_exists($pdo, 'products', 'isbn', 'isbn VARCHAR(20) NULL UNIQUE');
    ensure_column_exists($pdo, 'products', 'author', 'author VARCHAR(200) NULL');
    ensure_column_exists($pdo, 'products', 'publisher', 'publisher VARCHAR(200) NULL');
    ensure_column_exists($pdo, 'products', 'tax_rate', 'tax_rate DECIMAL(5,2) NULL DEFAULT NULL');
    ensure_column_exists($pdo, 'products', 'youtube_video_url', 'youtube_video_url VARCHAR(500) NULL');
    ensure_column_exists($pdo, 'products', 'is_featured', 'is_featured TINYINT(1) NOT NULL DEFAULT 0');
}

function youtube_embed_url(?string $url): ?string
{
    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }

    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $host = preg_replace('/^www\./', '', $host);
    $videoId = null;

    if ($host === 'youtu.be') {
        $videoId = trim($parts['path'] ?? '', '/');
    } elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
        $path = trim($parts['path'] ?? '', '/');
        parse_str($parts['query'] ?? '', $query);
        if ($path === 'watch') {
            $videoId = $query['v'] ?? null;
        } elseif (preg_match('#^(?:embed|shorts)/([^/]+)$#', $path, $matches)) {
            $videoId = $matches[1];
        }
    }

    if (!is_string($videoId) || !preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
        return null;
    }

    return 'https://www.youtube-nocookie.com/embed/' . $videoId . '?rel=0';
}

function ensure_product_images_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id BIGINT UNSIGNED NOT NULL,
        image VARCHAR(255) NOT NULL,
        is_primary TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_product_images_product FOREIGN KEY (product_id)
            REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX idx_product_images_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function get_product_images(PDO $pdo, int $productId): array
{
    ensure_product_images_table($pdo);
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function upload_product_images(array $files): array
{
    $uploaded = [];
    if (empty($files['name']) || !is_array($files['name'])) {
        return $uploaded;
    }
    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        [$filename, $error] = handle_image_upload([
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ], __DIR__ . '/../public/uploads', true);
        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }
        if ($filename !== null) {
            $uploaded[] = $filename;
        }
    }
    return $uploaded;
}

function save_product_images(PDO $pdo, int $productId, array $filenames, ?int $primaryId = null): array
{
    ensure_product_images_table($pdo);
    $existing = get_product_images($pdo, $productId);
    $nextOrder = count($existing);
    $insertedIds = [];
    $insert = $pdo->prepare('INSERT INTO product_images (product_id, image, is_primary, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($filenames as $filename) {
        $insert->execute([$productId, $filename, empty($existing) && $nextOrder === 0 ? 1 : 0, $nextOrder++]);
        $insertedIds[] = (int)$pdo->lastInsertId();
    }
    if ($primaryId !== null) {
        $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
        $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE product_id = ? AND id = ?')->execute([$productId, $primaryId]);
    }
    return $insertedIds;
}

function set_product_primary_image(PDO $pdo, int $productId, int $imageId): void
{
    ensure_product_images_table($pdo);
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
        $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE product_id = ? AND id = ?')->execute([$productId, $imageId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function product_isbn(PDO $pdo, ?string $isbn, ?int $productId = null): ?string
{
    $isbn = trim((string)$isbn);
    if ($isbn === '') {
        return null;
    }
    if (strlen($isbn) > 20) {
        throw new InvalidArgumentException('ISBN must be 20 characters or fewer.');
    }

    $sql = 'SELECT id FROM products WHERE isbn = ?';
    $params = [$isbn];
    if ($productId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $productId;
    }
    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn()) {
        throw new InvalidArgumentException('This ISBN is already assigned to another product.');
    }
    return $isbn;
}

// Image upload helper
function handle_image_upload(array $file, string $targetDir = __DIR__ . '/../public/uploads', bool $optimize = false): array
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
    if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }
    $base = bin2hex(random_bytes(8));
    $ext = $allowed[$mime];
    $filename = $base . '.' . $ext;
    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if ($optimize && function_exists('imagecreatefromjpeg') && function_exists('imagewebp')) {
        $imageInfo = @getimagesize($file['tmp_name']);
        $source = false;
        if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
            $source = @imagecreatefromjpeg($file['tmp_name']);
        } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
            $source = @imagecreatefrompng($file['tmp_name']);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $source = @imagecreatefromwebp($file['tmp_name']);
        }
        if ($imageInfo && $source !== false) {
            $sourceWidth = (int)$imageInfo[0];
            $sourceHeight = (int)$imageInfo[1];
            $scale = min(1, 1200 / $sourceWidth, 1600 / $sourceHeight);
            $width = max(1, (int)round($sourceWidth * $scale));
            $height = max(1, (int)round($sourceHeight * $scale));
            $resized = imagecreatetruecolor($width, $height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefill($resized, 0, 0, $transparent);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
            $filename = $base . '.webp';
            $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
            $saved = imagewebp($resized, $dest, 82);
            imagedestroy($resized);
            imagedestroy($source);
            $source = false;
            if ($saved) {
                return [$filename, null];
            }
        }
        if ($source !== false) {
            imagedestroy($source);
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [null, 'Failed to move uploaded file'];
    }
    return [$filename, null];
}

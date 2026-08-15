<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>\n';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';

// Homepage
$siteUrl = rtrim(getenv('BASE_URL') ?: '', '/');
echo "  <url><loc>" . htmlspecialchars(base_url()) . "</loc></url>\n";

// Products
$stmt = $pdo->query('SELECT id, slug, updated_at FROM products WHERE status = 1');
while ($r = $stmt->fetch()) {
    $loc = htmlspecialchars(base_url('product.php?id=' . $r['id']));
    echo "  <url><loc>$loc</loc><lastmod>" . date('Y-m-d', strtotime($r['updated_at'] ?? date('Y-m-d'))) . "</lastmod></url>\n";
}

// Categories
$stmt = $pdo->query('SELECT id, slug, updated_at FROM categories WHERE status = 1');
while ($r = $stmt->fetch()) {
    $loc = htmlspecialchars(base_url('products.php?category=' . $r['slug']));
    echo "  <url><loc>$loc</loc></url>\n";
}

echo '</urlset>';
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pdo = getPDO();
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>\n';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';

// Homepage
$siteUrl = (string)get_setting($pdo, 'site_url', '');
echo "  <url><loc>" . htmlspecialchars(absolute_url(base_url(), $siteUrl), ENT_XML1, 'UTF-8') . "</loc></url>\n";

// Products
$stmt = $pdo->query('SELECT id, slug, updated_at FROM products WHERE status = 1');
while ($r = $stmt->fetch()) {
    $loc = htmlspecialchars(absolute_url(product_url((string)$r['slug']), $siteUrl), ENT_XML1, 'UTF-8');
    echo "  <url><loc>$loc</loc><lastmod>" . date('Y-m-d', strtotime($r['updated_at'] ?? date('Y-m-d'))) . "</lastmod></url>\n";
}

// Categories
$stmt = $pdo->query('SELECT id, slug, updated_at FROM categories WHERE status = 1');
while ($r = $stmt->fetch()) {
    $loc = htmlspecialchars(absolute_url(base_url('products.php?category=' . rawurlencode((string)$r['slug'])), $siteUrl), ENT_XML1, 'UTF-8');
    echo "  <url><loc>$loc</loc></url>\n";
}

echo '</urlset>';
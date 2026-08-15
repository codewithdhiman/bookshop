<?php
// includes/header.php
require_once __DIR__ . '/functions.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookshop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link href="<?= base_url('public/css/design.css') ?>" rel="stylesheet">
    <link href="<?= base_url('public/css/style.css') ?>" rel="stylesheet">
    <?php
    // render SEO meta tags when possible
    $pdo = getPDO();
    render_seo_meta($pdo);
    ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
<div class="container mt-4">

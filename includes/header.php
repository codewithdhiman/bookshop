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
        <script>
            // Apply theme early to avoid flash: read saved theme or system
            (function(){
                try{
                    var t = localStorage.getItem('bookshop-theme');
                    if (!t || t === 'system'){
                        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                        t = prefersDark ? 'midnight' : 'classic';
                    }
                    document.documentElement.setAttribute('data-theme', t);
                }catch(e){}
            })();
        </script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
        <link href="<?= base_url('public/css/design.css') ?>" rel="stylesheet">
        <link href="<?= base_url('public/css/style.css') ?>" rel="stylesheet">
        <link rel="icon" type="image/png" href="<?= base_url('public/images/favicon.ico') ?>">
    <?php
    // render SEO meta tags when possible
    $pdo = getPDO();
    render_seo_meta($pdo);
    ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
<div class="site-wrapper">
    <main class="site-main">
        <div class="container mt-4">

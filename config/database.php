<?php
// config/database.php

declare(strict_types=1);

// Database credentials - edit these to match your environment
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bookshop');
define('DB_USER', 'dhiman');
define('DB_PASS', '12345');

// Base URL for the application (change if folder name changes)
// Example: '/bookshop' or '' for root
if (!defined('BASE_URL')) {
    define('BASE_URL', '/bookshop');
}

function base_url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return $base . '/' . ltrim($path, '/');
}

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

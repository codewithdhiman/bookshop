<?php
// includes/admin_auth.php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function require_admin()
{
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

<?php
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['user']);
}
header('Location: ' . base_url()); exit;

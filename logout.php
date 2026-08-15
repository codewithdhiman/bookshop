<?php
require_once __DIR__ . '/includes/functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// Clear all session data and destroy session to prevent fixation
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: ' . base_url()); exit;

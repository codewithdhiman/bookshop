<?php
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['user']);
header('Location: /admin/login.php'); exit;

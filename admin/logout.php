<?php
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['user']);
header('Location: ' . base_url('admin/login.php')); exit;

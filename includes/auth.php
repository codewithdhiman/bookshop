<?php
// includes/auth.php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function register_user(array $data): array
{
    $pdo = getPDO();
    // basic validation
    if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        return [false, 'Invalid email'];
    }
    if (strlen($data['password'] ?? '') < 6) {
        return [false, 'Password must be at least 6 characters'];
    }
    // check existing
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return [false, 'Email already registered'];
    }
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->execute([$data['name'], $data['email'], $hash, $data['phone'] ?? null, 'customer']);
    return [true, 'Registered successfully'];
}

function login_user(string $email, string $password): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return [false, 'Invalid credentials'];
    }
    if (!password_verify($password, $user['password'])) {
        return [false, 'Invalid credentials'];
    }
    // set session
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    return [true, 'Logged in'];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (!current_user()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

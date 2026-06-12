<?php
// ============================================================
// CraftBazaar — Login Handler
// POST /Backend/auth/login.php
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   =       $_POST['password']  ?? '';

if (empty($identifier) || empty($password)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Username/email dan password wajib diisi']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT id, username, email, password, role, is_active FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Username/email atau password salah']);
    exit;
}

if (!$user['is_active']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akun kamu dinonaktifkan. Hubungi admin.']);
    exit;
}

setUserSession($user);

// Redirect ke halaman HTML frontend (bukan PHP)
$redirect = match($user['role']) {
    'admin'  => 'admin-home.html',
    'seller' => 'admin-home.html',
    default  => 'Marketplace-main.html',
};

echo json_encode([
    'success'  => true,
    'message'  => 'Login berhasil! Selamat datang, ' . $user['username'],
    'role'     => $user['role'],
    'redirect' => $redirect,
]);
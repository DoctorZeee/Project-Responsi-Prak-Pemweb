<?php
// ============================================================
// CraftBazaar — Login Handler
// POST /auth/login.php
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');   // bisa email atau username
$password   =       $_POST['password']  ?? '';

//  Validasi input 
if (empty($identifier) || empty($password)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Username/email dan password wajib diisi']);
    exit;
}

//  Cari user 
$db   = getDB();
$stmt = $db->prepare('
    SELECT id, username, email, password, role, is_active
    FROM users
    WHERE email = ? OR username = ?
    LIMIT 1
');
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

//  Verifikasi password 
if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Username/email atau password salah']);
    exit;
}

//  Cek akun aktif 
if (!$user['is_active']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akun kamu dinonaktifkan. Hubungi admin.']);
    exit;
}

// Set session 
setUserSession($user);

// Redirect sesuai role 
$redirect = match($user['role']) {
    'admin'  => '/admin/dashboard.php',
    'seller' => '/seller/dashboard.php',
    default  => '/marketplace.php',
};

echo json_encode([
    'success'  => true,
    'message'  => 'Login berhasil! Selamat datang, ' . $user['username'],
    'role'     => $user['role'],
    'redirect' => $redirect,
]);

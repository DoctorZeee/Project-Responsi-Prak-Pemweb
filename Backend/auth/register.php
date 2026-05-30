<?php
// ============================================================
// CraftBazaar — Register Handler
// POST /auth/register.php
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helper.php';

header('Content-Type: application/json');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Ambil input
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password =       $_POST['password'] ?? '';
$confirm  =       $_POST['confirm_password'] ?? '';
$role     = trim($_POST['role']     ?? 'buyer');

// Validasi 
$errors = [];

if (empty($username) || strlen($username) < 3) {
    $errors[] = 'Username minimal 3 karakter';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
}
if (strlen($password) < 6) {
    $errors[] = 'Password minimal 6 karakter';
}
if ($password !== $confirm) {
    $errors[] = 'Konfirmasi password tidak cocok';
}
if (!in_array($role, ['buyer', 'seller'])) {
    $errors[] = 'Role tidak valid';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Cek duplikat 
$db = getDB();

$stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);

if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan']);
    exit;
}

// Simpan user
$hashed = password_hash($password, PASSWORD_BCRYPT);

$insert = $db->prepare('
    INSERT INTO users (username, email, password, role)
    VALUES (?, ?, ?, ?)
');
$insert->execute([$username, $email, $hashed, $role]);

$newUserId = $db->lastInsertId();

// Auto login setelah register 
setUserSession([
    'id'       => $newUserId,
    'username' => $username,
    'email'    => $email,
    'role'     => $role,
]);

http_response_code(201);
echo json_encode([
    'success'  => true,
    'message'  => 'Registrasi berhasil! Selamat datang di CraftBazaar.',
    'redirect' => getRedirectUrl($role),
]);

// Helper 
function getRedirectUrl(string $role): string {
    return match($role) {
        'seller' => '/seller/dashboard.php',
        default  => '/marketplace.php',
    };
}

<?php
// ============================================================
// CraftBazaar — Session & Auth Helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    // Cookie session aman via httponly; path "/" agar berlaku di seluruh subdirektori
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true jika pakai HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser(): array|null {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'],
    ];
}

function isRole(string ...$roles): bool {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['role'], $roles);
}

function isAdmin(): bool   { return isRole('admin'); }
function isSeller(): bool  { return isRole('seller'); }
function isBuyer(): bool   { return isRole('buyer'); }

// Guard: kembalikan JSON error jika tidak login / role salah
function requireLoginApi(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.', 'redirect' => 'Signin.html']);
        exit;
    }
}

function requireRoleApi(string ...$roles): void {
    requireLoginApi();
    if (!isRole(...$roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak. Role tidak sesuai.']);
        exit;
    }
}

// Set session setelah login
function setUserSession(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
}

// Destroy session (logout)
function destroySession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
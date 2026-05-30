<?php
// ============================================================
// CraftBazaar — Session & Auth Helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// Cek apakah user sudah login
// ------------------------------------------------------------
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ------------------------------------------------------------
// Ambil data user yang sedang login
// ------------------------------------------------------------
function currentUser(): array|null {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'],
    ];
}

// ------------------------------------------------------------
// Cek role
// ------------------------------------------------------------
function isRole(string ...$roles): bool {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['role'], $roles);
}

function isAdmin(): bool   { return isRole('admin'); }
function isSeller(): bool  { return isRole('seller'); }
function isBuyer(): bool   { return isRole('buyer'); }

// ------------------------------------------------------------
// Guard: redirect kalau tidak login
// ------------------------------------------------------------
function requireLogin(string $redirect = '/auth/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

// ------------------------------------------------------------
// Guard: redirect kalau role tidak sesuai
// ------------------------------------------------------------
function requireRole(string $redirect = '/', string ...$roles): void {
    requireLogin();
    if (!isRole(...$roles)) {
        header("Location: $redirect");
        exit;
    }
}

// ------------------------------------------------------------
// Set session setelah login berhasil
// ------------------------------------------------------------
function setUserSession(array $user): void {
    session_regenerate_id(true);     // prevent session fixation
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
}

// ------------------------------------------------------------
// Destroy session (logout)
// ------------------------------------------------------------
function destroySession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// ------------------------------------------------------------
// Redirect sesuai role setelah login
// ------------------------------------------------------------
function redirectByRole(): void {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'seller':
            header('Location: /seller/dashboard.php');
            break;
        default:
            header('Location: /marketplace.php');
            break;
    }
    exit;
}

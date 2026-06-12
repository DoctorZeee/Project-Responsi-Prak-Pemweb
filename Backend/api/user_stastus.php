<?php
// ============================================================
// CraftBazaar — API Check Session Status
// GET /api/user_status.php
// ============================================================

require_once __DIR__ . '/../includes/auth_helper.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'user' => currentUser() // Mengambil id, username, email, dan role
    ]);
} else {
    echo json_encode([
        'success' => true,
        'logged_in' => false
    ]);
}
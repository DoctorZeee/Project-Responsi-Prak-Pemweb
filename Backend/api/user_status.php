<?php
// ============================================================
// CraftBazaar — API: Check Session Status
// GET /Backend/api/user_status.php
// FIX: file sebelumnya bernama user_stastus.php (typo)
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helper.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    $user = currentUser();
    // Ambil juga saldo & cart_count dari DB (data yang sering dibutuhkan navbar)
    $db = getDB();

    $balanceStmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
    $balanceStmt->execute([$user['id']]);
    $balanceRow = $balanceStmt->fetch();

    $cartStmt = $db->prepare('SELECT COALESCE(SUM(quantity), 0) AS cnt FROM cart WHERE user_id = ?');
    $cartStmt->execute([$user['id']]);
    $cartRow = $cartStmt->fetch();

    echo json_encode([
        'success'    => true,
        'logged_in'  => true,
        'user'       => array_merge($user, [
            'balance'    => (float) ($balanceRow['balance'] ?? 0),
            'cart_count' => (int)   ($cartRow['cnt'] ?? 0),
        ]),
    ]);
} else {
    echo json_encode(['success' => true, 'logged_in' => false]);
}
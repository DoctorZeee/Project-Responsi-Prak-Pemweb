<?php
// ============================================================
// CraftBazaar — Reviews: Delete Review
// POST /buyer/reviews/delete.php
// Buyer hapus miliknya sendiri, atau admin hapus semua
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'buyer', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db       = getDB();
$userId   = currentUser()['id'];
$reviewId = (int) ($_POST['id'] ?? 0);

if ($reviewId <= 0) {
    jsonResponse(false, 'ID review tidak valid', [], 400);
}

$checkSQL    = isAdmin()
    ? 'SELECT id, item_id FROM reviews WHERE id = ?'
    : 'SELECT id, item_id FROM reviews WHERE id = ? AND user_id = ?';
$checkParams = isAdmin() ? [$reviewId] : [$reviewId, $userId];

$stmt   = $db->prepare($checkSQL);
$stmt->execute($checkParams);
$review = $stmt->fetch();

if (!$review) {
    jsonResponse(false, 'Review tidak ditemukan atau bukan milik kamu', [], 404);
}

$db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$reviewId]);

// Recalculate avg rating
recalcAvgRating($db, $review['item_id']);

jsonResponse(true, 'Review berhasil dihapus.');

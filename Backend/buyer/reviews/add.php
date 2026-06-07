<?php
// ============================================================
// CraftBazaar — Reviews: Add Review
// POST /buyer/reviews/add.php
// Hanya buyer yang sudah punya order completed untuk item tsb
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db      = getDB();
$userId  = currentUser()['id'];
$itemId  = (int)    ($_POST['item_id'] ?? 0);
$rating  = (int)    ($_POST['rating']  ?? 0);
$comment = sanitize($_POST['comment']  ?? '');

// Validasi dasar
if ($itemId <= 0 || $rating < 1 || $rating > 5) {
    jsonResponse(false, 'Data tidak valid. Rating harus antara 1–5.', [], 400);
}

// Cek item ada
$item = $db->prepare('SELECT id FROM items WHERE id = ? AND is_approved = 1');
$item->execute([$itemId]);
if (!$item->fetch()) {
    jsonResponse(false, 'Item tidak ditemukan', [], 404);
}

// Cek sudah pernah review
$already = $db->prepare('SELECT id FROM reviews WHERE item_id = ? AND user_id = ?');
$already->execute([$itemId, $userId]);
if ($already->fetch()) {
    jsonResponse(false, 'Kamu sudah pernah mereview item ini.', [], 409);
}

// Cek buyer pernah beli dan order sudah completed
$boughtCheck = $db->prepare('
    SELECT oi.id
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE oi.item_id = ? AND o.buyer_id = ? AND o.status = \'completed\'
    LIMIT 1
');
$boughtCheck->execute([$itemId, $userId]);
if (!$boughtCheck->fetch()) {
    jsonResponse(false, 'Kamu hanya bisa mereview item yang sudah kamu beli dan order-nya selesai.', [], 403);
}

$db->prepare('
    INSERT INTO reviews (item_id, user_id, rating, comment)
    VALUES (?, ?, ?, ?)
')->execute([$itemId, $userId, $rating, $comment]);

// Update avg rating di tabel items
recalcAvgRating($db, $itemId);

jsonResponse(true, 'Review berhasil ditambahkan. Terima kasih!', [], 201);

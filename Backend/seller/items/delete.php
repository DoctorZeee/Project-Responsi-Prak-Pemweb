<?php
// ============================================================
// CraftBazaar — Seller: Delete Item
// POST /seller/items/delete.php
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'seller', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db     = getDB();
$itemId = (int) ($_POST['id'] ?? 0);

if ($itemId <= 0) {
    jsonResponse(false, 'ID item tidak valid', [], 400);
}

$sellerId  = currentUser()['id'];
$checkSQL  = isAdmin()
    ? 'SELECT id, image FROM items WHERE id = ?'
    : 'SELECT id, image FROM items WHERE id = ? AND seller_id = ?';
$params = isAdmin() ? [$itemId] : [$itemId, $sellerId];

$stmt = $db->prepare($checkSQL);
$stmt->execute($params);
$item = $stmt->fetch();

if (!$item) {
    jsonResponse(false, 'Item tidak ditemukan atau bukan milik kamu', [], 404);
}

// Cek apakah item ada di order aktif
$inOrder = $db->prepare('
    SELECT COUNT(*) FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE oi.item_id = ? AND o.status IN (\'pending\', \'paid\')
');
$inOrder->execute([$itemId]);
if ((int)$inOrder->fetchColumn() > 0) {
    jsonResponse(false, 'Item tidak bisa dihapus karena ada di order yang sedang berjalan.', [], 409);
}

// Hapus gambar
$uploadDir = __DIR__ . '/../../uploads/items/';
if ($item['image'] && file_exists($uploadDir . $item['image'])) {
    unlink($uploadDir . $item['image']);
}

$db->prepare('DELETE FROM items WHERE id = ?')->execute([$itemId]);

jsonResponse(true, 'Item berhasil dihapus.');

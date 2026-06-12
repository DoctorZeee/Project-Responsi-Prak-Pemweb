<?php
// ============================================================
// CraftBazaar — Seller: Delete Item
// POST /Backend/seller/items/delete.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('seller', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed', [], 405);

$db       = getDB();
$sellerId = currentUser()['id'];
$itemId   = (int)($_POST['item_id'] ?? 0);

if ($itemId <= 0) jsonResponse(false, 'item_id wajib diisi', [], 400);

$check = $db->prepare('SELECT id, image FROM items WHERE id = ?' . (isAdmin() ? '' : ' AND seller_id = ?'));
$args  = isAdmin() ? [$itemId] : [$itemId, $sellerId];
$check->execute($args);
$item = $check->fetch();

if (!$item) jsonResponse(false, 'Item tidak ditemukan atau bukan milik Anda', [], 404);

// Hapus gambar jika ada
if ($item['image']) {
    $imgPath = __DIR__ . '/../../uploads/items/' . $item['image'];
    if (file_exists($imgPath)) @unlink($imgPath);
}

$db->prepare('DELETE FROM items WHERE id = ?')->execute([$itemId]);

jsonResponse(true, 'Item berhasil dihapus.');
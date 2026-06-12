<?php
// ============================================================
// CraftBazaar — Cart: Add to Cart
// POST /Backend/buyer/cart/add.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed', [], 405);

$db       = getDB();
$userId   = currentUser()['id'];
$itemId   = (int)($_POST['item_id']  ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($itemId <= 0 || $quantity < 1) jsonResponse(false, 'Data tidak valid', [], 400);

$item = $db->prepare('SELECT id, seller_id, name, price, stock, is_active, is_approved FROM items WHERE id = ?');
$item->execute([$itemId]);
$item = $item->fetch();

if (!$item)                             jsonResponse(false, 'Item tidak ditemukan', [], 404);
if (!$item['is_active'])                jsonResponse(false, 'Item tidak aktif', [], 400);
if (!$item['is_approved'])              jsonResponse(false, 'Item belum disetujui', [], 400);
if ($item['seller_id'] == $userId)      jsonResponse(false, 'Tidak bisa membeli item milik sendiri', [], 400);
if ($quantity > $item['stock'])         jsonResponse(false, 'Stok tidak cukup. Tersisa: ' . $item['stock'], [], 400);

$existing = $db->prepare('SELECT id, quantity FROM cart WHERE user_id = ? AND item_id = ?');
$existing->execute([$userId, $itemId]);
$cartRow  = $existing->fetch();

if ($cartRow) {
    $newQty = $cartRow['quantity'] + $quantity;
    if ($newQty > $item['stock']) jsonResponse(false, 'Total di cart melebihi stok', [], 400);
    $db->prepare('UPDATE cart SET quantity = ? WHERE id = ?')->execute([$newQty, $cartRow['id']]);
    $msg = 'Jumlah item di cart diperbarui.';
} else {
    $db->prepare('INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, ?)')->execute([$userId, $itemId, $quantity]);
    $msg = 'Item berhasil ditambahkan ke cart.';
}

$countStmt = $db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?');
$countStmt->execute([$userId]);

jsonResponse(true, $msg, ['cart_count' => (int)$countStmt->fetchColumn()]);
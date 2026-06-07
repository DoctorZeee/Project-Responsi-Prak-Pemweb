<?php
// ============================================================
// CraftBazaar — Cart: Add to Cart
// POST /buyer/cart/add.php
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db       = getDB();
$userId   = currentUser()['id'];
$itemId   = (int) ($_POST['item_id']  ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);

if ($itemId <= 0 || $quantity < 1) {
    jsonResponse(false, 'Data tidak valid', [], 400);
}

// Cek item tersedia dan sudah approved
$item = $db->prepare('
    SELECT id, seller_id, name, price, stock, is_active, is_approved
    FROM items WHERE id = ?
');
$item->execute([$itemId]);
$item = $item->fetch();

if (!$item) {
    jsonResponse(false, 'Item tidak ditemukan', [], 404);
}
if (!$item['is_active'] || !$item['is_approved']) {
    jsonResponse(false, 'Item tidak tersedia saat ini', [], 400);
}
if ($item['seller_id'] == $userId) {
    jsonResponse(false, 'Kamu tidak bisa membeli item milik sendiri', [], 400);
}
if ($quantity > $item['stock']) {
    jsonResponse(false, 'Stok tidak mencukupi. Tersisa: ' . $item['stock'], [], 400);
}

// Cek apakah sudah ada di cart
$existing = $db->prepare('SELECT id, quantity FROM cart WHERE user_id = ? AND item_id = ?');
$existing->execute([$userId, $itemId]);
$cartRow = $existing->fetch();

if ($cartRow) {
    $newQty = $cartRow['quantity'] + $quantity;
    if ($newQty > $item['stock']) {
        jsonResponse(false, 'Total di cart melebihi stok tersedia', [], 400);
    }
    $db->prepare('UPDATE cart SET quantity = ? WHERE id = ?')
       ->execute([$newQty, $cartRow['id']]);
    $msg = 'Jumlah item di cart diperbarui.';
} else {
    $db->prepare('INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, ?)')
       ->execute([$userId, $itemId, $quantity]);
    $msg = 'Item berhasil ditambahkan ke cart.';
}

// Hitung total item di cart
$countStmt = $db->prepare('SELECT SUM(quantity) FROM cart WHERE user_id = ?');
$countStmt->execute([$userId]);
$cartCount = (int) $countStmt->fetchColumn();

jsonResponse(true, $msg, ['cart_count' => $cartCount]);

<?php
// ============================================================
// CraftBazaar — Cart: Update Quantity
// POST /buyer/cart/update.php
// body: cart_id, quantity (0 = hapus)
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRoleApi('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db      = getDB();
$userId  = currentUser()['id'];
$cartId  = (int) ($_POST['cart_id']  ?? 0);
$qty     = (int) ($_POST['quantity'] ?? -1);

if ($cartId <= 0 || $qty < 0) {
    jsonResponse(false, 'Data tidak valid', [], 400);
}

// Pastikan cart_id milik user ini
$row = $db->prepare('
    SELECT c.id, c.item_id, i.stock
    FROM cart c JOIN items i ON i.id = c.item_id
    WHERE c.id = ? AND c.user_id = ?
');
$row->execute([$cartId, $userId]);
$cart = $row->fetch();

if (!$cart) {
    jsonResponse(false, 'Item cart tidak ditemukan', [], 404);
}

if ($qty === 0) {
    $db->prepare('DELETE FROM cart WHERE id = ?')->execute([$cartId]);
    jsonResponse(true, 'Item dihapus dari cart.');
}

if ($qty > $cart['stock']) {
    jsonResponse(false, 'Jumlah melebihi stok tersedia (' . $cart['stock'] . ')', [], 400);
}

$db->prepare('UPDATE cart SET quantity = ? WHERE id = ?')->execute([$qty, $cartId]);
jsonResponse(true, 'Jumlah diperbarui.', ['new_quantity' => $qty]);

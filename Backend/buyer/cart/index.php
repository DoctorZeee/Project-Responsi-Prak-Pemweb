<?php
// ============================================================
// CraftBazaar — Cart: View Cart
// GET /Backend/buyer/cart/index.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('buyer');

$db     = getDB();
$userId = currentUser()['id'];

$stmt = $db->prepare("
    SELECT c.id AS cart_id, c.quantity,
           i.id AS item_id, i.name, i.slug, i.price, i.stock,
           i.image, i.rarity, i.is_active, i.is_approved,
           cat.name AS category_name,
           u.username AS seller_name
    FROM cart c
    JOIN items i       ON i.id = c.item_id
    JOIN categories cat ON cat.id = i.category_id
    JOIN users u       ON u.id = i.seller_id
    WHERE c.user_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as &$row) {
    $row['subtotal'] = (float)$row['price'] * (int)$row['quantity'];
    $subtotal += $row['subtotal'];
}
unset($row);

// Ambil juga saldo buyer
$balStmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
$balStmt->execute([$userId]);
$balance = (float)($balStmt->fetchColumn() ?? 0);

echo json_encode([
    'success'  => true,
    'data'     => $cartItems,
    'subtotal' => $subtotal,
    'balance'  => $balance,
]);
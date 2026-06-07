<?php
// ============================================================
// CraftBazaar — Cart: View Cart
// GET /buyer/cart/index.php
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'buyer');

$db     = getDB();
$userId = currentUser()['id'];

$stmt = $db->prepare('
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
');
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as &$row) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $subtotal += $row['subtotal'];
}
unset($row);

header('Content-Type: application/json');
echo json_encode([
    'success'  => true,
    'data'     => $cartItems,
    'subtotal' => $subtotal,
]);

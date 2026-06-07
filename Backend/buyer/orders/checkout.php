<?php
// ============================================================
// CraftBazaar — Order: Checkout
// POST /buyer/orders/checkout.php
// Mengubah cart menjadi order, potong saldo buyer
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db     = getDB();
$userId = currentUser()['id'];
$note   = sanitize($_POST['note'] ?? '');

// Ambil semua item di cart
$cartStmt = $db->prepare('
    SELECT c.id AS cart_id, c.quantity,
           i.id AS item_id, i.seller_id, i.name,
           i.price, i.stock, i.is_active, i.is_approved
    FROM cart c
    JOIN items i ON i.id = c.item_id
    WHERE c.user_id = ?
');
$cartStmt->execute([$userId]);
$cartItems = $cartStmt->fetchAll();

if (empty($cartItems)) {
    jsonResponse(false, 'Cart kosong', [], 400);
}

// Validasi stok & ketersediaan
$total = 0;
foreach ($cartItems as $ci) {
    if (!$ci['is_active'] || !$ci['is_approved']) {
        jsonResponse(false, "Item \"{$ci['name']}\" tidak tersedia lagi.", [], 400);
    }
    if ($ci['quantity'] > $ci['stock']) {
        jsonResponse(false, "Stok \"{$ci['name']}\" tidak mencukupi. Tersisa: {$ci['stock']}", [], 400);
    }
    $total += $ci['price'] * $ci['quantity'];
}

// Cek saldo buyer
$buyerStmt = $db->prepare('SELECT balance FROM users WHERE id = ?');
$buyerStmt->execute([$userId]);
$buyer = $buyerStmt->fetch();

if ((float)$buyer['balance'] < $total) {
    jsonResponse(false, 'Saldo tidak mencukupi. Saldo kamu: ' . formatRupiah($buyer['balance']) . ', Total: ' . formatRupiah($total), [], 400);
}

// Mulai transaksi DB
$db->beginTransaction();

try {
    // Buat order
    $orderStmt = $db->prepare('
        INSERT INTO orders (buyer_id, total_price, status, note)
        VALUES (?, ?, \'paid\', ?)
    ');
    $orderStmt->execute([$userId, $total, $note]);
    $orderId = (int) $db->lastInsertId();

    // Insert order_items + update stok
    $oiStmt = $db->prepare('
        INSERT INTO order_items (order_id, item_id, seller_id, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stockStmt = $db->prepare('
        UPDATE items SET stock = stock - ?, total_sold = total_sold + ? WHERE id = ?
    ');

    foreach ($cartItems as $ci) {
        $oiStmt->execute([$orderId, $ci['item_id'], $ci['seller_id'], $ci['quantity'], $ci['price']]);
        $stockStmt->execute([$ci['quantity'], $ci['quantity'], $ci['item_id']]);
    }

    // Potong saldo buyer
    $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
       ->execute([$total, $userId]);

    // Hapus cart
    $db->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);

    $db->commit();

    jsonResponse(true, 'Checkout berhasil!', [
        'order_id'    => $orderId,
        'total_price' => $total,
        'redirect'    => "/buyer/orders/detail.php?id=$orderId",
    ]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(false, 'Terjadi kesalahan saat checkout. Silakan coba lagi.', [], 500);
}

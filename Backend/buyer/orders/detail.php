<?php
// ============================================================
// CraftBazaar — Order: Detail
// GET  /buyer/orders/detail.php?id=X   → tampilkan detail
// POST /buyer/orders/detail.php        → update status (complete/cancel)
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRoleApi('buyer', 'admin');

$db     = getDB();
$userId = currentUser()['id'];

// -------- POST: update status --------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId   = (int)    ($_POST['order_id'] ?? 0);
    $newStatus = sanitize($_POST['status']    ?? '');

    if ($orderId <= 0 || !in_array($newStatus, ['completed', 'cancelled'])) {
        jsonResponse(false, 'Data tidak valid', [], 400);
    }

    $checkSQL = isAdmin()
        ? 'SELECT id, status, buyer_id, total_price FROM orders WHERE id = ?'
        : 'SELECT id, status, buyer_id, total_price FROM orders WHERE id = ? AND buyer_id = ?';
    $checkParams = isAdmin() ? [$orderId] : [$orderId, $userId];

    $orderStmt = $db->prepare($checkSQL);
    $orderStmt->execute($checkParams);
    $order = $orderStmt->fetch();

    if (!$order) {
        jsonResponse(false, 'Order tidak ditemukan', [], 404);
    }
    if (!in_array($order['status'], ['paid', 'pending'])) {
        jsonResponse(false, 'Order sudah ' . $order['status'] . ' dan tidak bisa diubah.', [], 409);
    }

    $db->beginTransaction();
    try {
        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')
           ->execute([$newStatus, $orderId]);

        // Jika cancel, kembalikan stok
        if ($newStatus === 'cancelled') {
            $items = $db->prepare('SELECT item_id, quantity FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            $restoreStmt = $db->prepare('UPDATE items SET stock = stock + ?, total_sold = total_sold - ? WHERE id = ?');
            foreach ($items->fetchAll() as $oi) {
                $restoreStmt->execute([$oi['quantity'], $oi['quantity'], $oi['item_id']]);
            }
            // Kembalikan saldo ke buyer jika sudah paid
            if ($order['status'] === 'paid') {
                $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')
                   ->execute([$order['total_price'], $order['buyer_id']]);
            }
        }

        $db->commit();
        jsonResponse(true, 'Status order diperbarui ke ' . $newStatus);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false, 'Gagal update status', [], 500);
    }
}

// -------- GET: detail order ----------------------------------
$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId <= 0) {
    jsonResponse(false, 'ID order tidak valid', [], 400);
}

$checkSQL    = isAdmin()
    ? 'SELECT o.*, u.username AS buyer_name FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = ?'
    : 'SELECT o.*, u.username AS buyer_name FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.id = ? AND o.buyer_id = ?';
$checkParams = isAdmin() ? [$orderId] : [$orderId, $userId];

$orderStmt = $db->prepare($checkSQL);
$orderStmt->execute($checkParams);
$order = $orderStmt->fetch();

if (!$order) {
    jsonResponse(false, 'Order tidak ditemukan', [], 404);
}

$itemsStmt = $db->prepare('
    SELECT oi.quantity, oi.price,
           i.name, i.slug, i.image, i.rarity,
           u.username AS seller_name
    FROM order_items oi
    JOIN items i ON i.id = oi.item_id
    JOIN users u ON u.id = oi.seller_id
    WHERE oi.order_id = ?
');
$itemsStmt->execute([$orderId]);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => array_merge($order, ['items' => $itemsStmt->fetchAll()]),
]);

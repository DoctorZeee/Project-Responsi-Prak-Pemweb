<?php
// ============================================================
// CraftBazaar — Admin: Manage Orders
// GET  /admin/orders/index.php   → list semua order
// POST /admin/orders/index.php   → update status order
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'admin');

$db = getDB();

// -------- POST: update status --------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId   = (int)    ($_POST['order_id'] ?? 0);
    $newStatus = sanitize($_POST['status']    ?? '');

    if ($orderId <= 0 || !in_array($newStatus, ['pending','paid','completed','cancelled'])) {
        jsonResponse(false, 'Data tidak valid', [], 400);
    }

    $order = $db->prepare('SELECT id, status, buyer_id, total_price FROM orders WHERE id = ?');
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) jsonResponse(false, 'Order tidak ditemukan', [], 404);

    $db->beginTransaction();
    try {
        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);

        if ($newStatus === 'cancelled' && in_array($order['status'], ['paid', 'pending'])) {
            // Kembalikan stok
            $items = $db->prepare('SELECT item_id, quantity FROM order_items WHERE order_id = ?');
            $items->execute([$orderId]);
            $restoreStmt = $db->prepare('UPDATE items SET stock = stock + ?, total_sold = total_sold - ? WHERE id = ?');
            foreach ($items->fetchAll() as $oi) {
                $restoreStmt->execute([$oi['quantity'], $oi['quantity'], $oi['item_id']]);
            }
            // Kembalikan saldo buyer jika sudah paid
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

// -------- GET: list all orders -------------------------------
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$status  = sanitize($_GET['status'] ?? '');
$search  = sanitize($_GET['search'] ?? '');

$where  = [];
$params = [];

if (in_array($status, ['pending','paid','completed','cancelled'])) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}
if ($search) {
    $where[]  = 'u.username LIKE ?';
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.buyer_id $whereSQL");
$countStmt->execute($params);
$total    = (int) $countStmt->fetchColumn();
$lastPage = (int) ceil($total / $perPage);

$ordStmt = $db->prepare("
    SELECT o.id, o.total_price, o.status, o.created_at,
           u.username AS buyer_name, u.email AS buyer_email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    $whereSQL
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$ordStmt->execute($params);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $ordStmt->fetchAll(),
    'meta'    => ['total' => $total, 'page' => $page, 'last_page' => $lastPage],
]);

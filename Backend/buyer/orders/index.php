<?php
// ============================================================
// CraftBazaar — Order: History
// GET /buyer/orders/index.php
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'buyer');

$db     = getDB();
$userId = currentUser()['id'];

$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Filter status
$status      = sanitize($_GET['status'] ?? '');
$validStatus = ['pending','paid','completed','cancelled'];

$where  = ['o.buyer_id = ?'];
$params = [$userId];

if (in_array($status, $validStatus)) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o $whereSQL");
$countStmt->execute($params);
$total    = (int) $countStmt->fetchColumn();
$lastPage = (int) ceil($total / $perPage);

$ordStmt = $db->prepare("
    SELECT o.id, o.total_price, o.status, o.note, o.created_at,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    $whereSQL
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$ordStmt->execute($params);
$orders = $ordStmt->fetchAll();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $orders,
    'meta'    => ['total' => $total, 'page' => $page, 'last_page' => $lastPage],
]);

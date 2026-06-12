<?php
// ============================================================
// CraftBazaar — Order: History
// GET /Backend/buyer/orders/index.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('buyer');

$db     = getDB();
$userId = currentUser()['id'];

$perPage     = 10;
$page        = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page - 1) * $perPage;
$status      = sanitize($_GET['status'] ?? '');
$validStatus = ['pending','paid','completed','cancelled'];

$where  = ['o.buyer_id = ?'];
$params = [$userId];

if (in_array($status, $validStatus)) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}

$whereSQL  = 'WHERE ' . implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o $whereSQL");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$lastPage = max(1, (int)ceil($total / $perPage));

$ordStmt = $db->prepare("
    SELECT o.id, o.total_price, o.status, o.note, o.created_at,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count,
           (SELECT GROUP_CONCAT(i.name SEPARATOR ', ')
            FROM order_items oi JOIN items i ON i.id = oi.item_id
            WHERE oi.order_id = o.id LIMIT 3) AS item_names
    FROM orders o
    $whereSQL
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$ordStmt->execute($params);

echo json_encode([
    'success' => true,
    'data'    => $ordStmt->fetchAll(),
    'meta'    => ['total' => $total, 'page' => $page, 'last_page' => $lastPage],
]);
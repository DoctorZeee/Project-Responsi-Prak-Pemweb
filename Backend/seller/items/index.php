<?php
// ============================================================
// CraftBazaar — Seller: List My Items
// GET /seller/items/index.php
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'seller', 'admin');

$db       = getDB();
$sellerId = currentUser()['id'];

// Pagination
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Filter
$search  = sanitize($_GET['search']   ?? '');
$catId   = (int)   ($_GET['category'] ?? 0);
$status  = sanitize($_GET['status']   ?? ''); // approved | pending

$where  = ['i.seller_id = ?'];
$params = [$sellerId];

if ($search) {
    $where[]  = 'i.name LIKE ?';
    $params[] = "%$search%";
}
if ($catId) {
    $where[]  = 'i.category_id = ?';
    $params[] = $catId;
}
if ($status === 'approved') {
    $where[]  = 'i.is_approved = 1';
} elseif ($status === 'pending') {
    $where[]  = 'i.is_approved = 0';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Total rows
$countStmt = $db->prepare("SELECT COUNT(*) FROM items i $whereSQL");
$countStmt->execute($params);
$total    = (int) $countStmt->fetchColumn();
$lastPage = (int) ceil($total / $perPage);

// Items
$itemStmt = $db->prepare("
    SELECT i.id, i.name, i.slug, i.price, i.rarity, i.stock,
           i.is_approved, i.is_active, i.total_sold, i.avg_rating,
           i.image, i.created_at,
           c.name AS category_name
    FROM items i
    JOIN categories c ON c.id = i.category_id
    $whereSQL
    ORDER BY i.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$itemStmt->execute($params);
$items = $itemStmt->fetchAll();

// Kembalikan JSON (frontend fetch atau PHP include)
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $items,
    'meta'    => [
        'total'    => $total,
        'per_page' => $perPage,
        'page'     => $page,
        'last_page'=> $lastPage,
    ],
]);

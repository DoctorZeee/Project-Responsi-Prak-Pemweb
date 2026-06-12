<?php
// ============================================================
// CraftBazaar — Seller: List My Items
// GET /Backend/seller/items/index.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('seller', 'admin');

$db       = getDB();
$sellerId = currentUser()['id'];

$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = sanitize($_GET['search'] ?? '');

$where  = ['i.seller_id = ?'];
$params = [$sellerId];

if ($search) {
    $where[]  = 'i.name LIKE ?';
    $params[] = "%$search%";
}

$whereSQL  = 'WHERE ' . implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) FROM items i $whereSQL");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$lastPage = max(1, (int)ceil($total / $perPage));

$itemStmt = $db->prepare("
    SELECT i.id, i.name, i.slug, i.price, i.rarity, i.stock,
           i.is_approved, i.is_active, i.total_sold, i.avg_rating, i.image, i.created_at,
           c.name AS category_name
    FROM items i JOIN categories c ON c.id = i.category_id
    $whereSQL
    ORDER BY i.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$itemStmt->execute($params);

// Statistik singkat untuk seller
$statsStmt = $db->prepare("
    SELECT
        COUNT(*) AS total_items,
        COALESCE(SUM(i.total_sold), 0) AS total_sold,
        COALESCE(SUM(i.total_sold * i.price), 0) AS total_revenue
    FROM items i WHERE i.seller_id = ?
");
$statsStmt->execute([$sellerId]);
$stats = $statsStmt->fetch();

// Kategori untuk form tambah item
$cats = $db->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();

echo json_encode([
    'success'    => true,
    'data'       => $itemStmt->fetchAll(),
    'stats'      => $stats,
    'categories' => $cats,
    'meta'       => ['total' => $total, 'page' => $page, 'last_page' => $lastPage],
]);
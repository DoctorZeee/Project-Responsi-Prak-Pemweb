<?php
// ============================================================
// CraftBazaar — Public: Marketplace Item List
// GET /api/items.php
// Tidak butuh login — data untuk halaman marketplace
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDB();

$perPage  = (int) ($_GET['per_page'] ?? 12);
$perPage  = min(max($perPage, 1), 50);
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$search   = sanitize($_GET['search']   ?? '');
$catId    = (int)   ($_GET['category'] ?? 0);
$rarity   = sanitize($_GET['rarity']   ?? '');
$sort     = sanitize($_GET['sort']     ?? 'newest');
$minPrice = (float) ($_GET['min_price'] ?? 0);
$maxPrice = (float) ($_GET['max_price'] ?? 0);

$where  = ['i.is_approved = 1', 'i.is_active = 1', 'i.stock > 0'];
$params = [];

if ($search) {
    $where[]  = '(i.name LIKE ? OR i.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catId) {
    $where[]  = 'i.category_id = ?';
    $params[] = $catId;
}
if (in_array($rarity, ['common','uncommon','rare','epic','legendary'])) {
    $where[]  = 'i.rarity = ?';
    $params[] = $rarity;
}
if ($minPrice > 0) {
    $where[]  = 'i.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice > 0) {
    $where[]  = 'i.price <= ?';
    $params[] = $maxPrice;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderSQL = match($sort) {
    'price_asc'   => 'i.price ASC',
    'price_desc'  => 'i.price DESC',
    'popular'     => 'i.total_sold DESC',
    'rating'      => 'i.avg_rating DESC',
    default       => 'i.created_at DESC',
};

$countStmt = $db->prepare("SELECT COUNT(*) FROM items i $whereSQL");
$countStmt->execute($params);
$total    = (int) $countStmt->fetchColumn();
$lastPage = (int) ceil($total / $perPage);

$itemStmt = $db->prepare("
    SELECT i.id, i.name, i.slug, i.price, i.rarity,
           i.image, i.avg_rating, i.total_sold,
           c.name AS category_name, c.slug AS category_slug,
           u.username AS seller_name
    FROM items i
    JOIN categories c ON c.id = i.category_id
    JOIN users u       ON u.id = i.seller_id
    $whereSQL
    ORDER BY $orderSQL
    LIMIT $perPage OFFSET $offset
");
$itemStmt->execute($params);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $itemStmt->fetchAll(),
    'meta'    => [
        'total'     => $total,
        'per_page'  => $perPage,
        'page'      => $page,
        'last_page' => $lastPage,
    ],
]);

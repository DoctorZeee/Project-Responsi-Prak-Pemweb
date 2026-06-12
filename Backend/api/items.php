<?php
// ============================================================
// CraftBazaar — Public API: Marketplace Item List
// GET /Backend/api/items.php
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$db = getDB();

$perPage  = min(max((int)($_GET['per_page'] ?? 12), 1), 50);
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$search   = sanitize($_GET['search']   ?? '');
$catSlug  = sanitize($_GET['category'] ?? '');
$rarity   = sanitize($_GET['rarity']   ?? '');
$sort     = sanitize($_GET['sort']     ?? 'newest');
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);

$where  = ['i.is_approved = 1', 'i.is_active = 1', 'i.stock > 0'];
$params = [];

if ($search) {
    $where[]  = '(i.name LIKE ? OR i.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catSlug) {
    $where[]  = 'c.slug = ?';
    $params[] = $catSlug;
}
if (in_array($rarity, ['common','uncommon','rare','epic','legendary'])) {
    $where[]  = 'i.rarity = ?';
    $params[] = $rarity;
}
if ($minPrice > 0) { $where[] = 'i.price >= ?'; $params[] = $minPrice; }
if ($maxPrice > 0) { $where[] = 'i.price <= ?'; $params[] = $maxPrice; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$orderSQL = match($sort) {
    'price_asc'  => 'i.price ASC',
    'price_desc' => 'i.price DESC',
    'popular'    => 'i.total_sold DESC',
    'rating'     => 'i.avg_rating DESC',
    default      => 'i.created_at DESC',
};

$countStmt = $db->prepare("SELECT COUNT(*) FROM items i JOIN categories c ON c.id = i.category_id $whereSQL");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$lastPage = max(1, (int)ceil($total / $perPage));

$itemStmt = $db->prepare("
    SELECT i.id, i.name, i.slug, i.price, i.rarity,
           i.image, i.avg_rating, i.total_sold, i.stock,
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

// Ambil semua kategori untuk filter
$cats = $db->query('SELECT id, name, slug, icon FROM categories ORDER BY name')->fetchAll();

echo json_encode([
    'success'    => true,
    'data'       => $itemStmt->fetchAll(),
    'categories' => $cats,
    'meta'       => ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'last_page' => $lastPage],
]);
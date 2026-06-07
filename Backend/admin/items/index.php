<?php
// ============================================================
// CraftBazaar — Admin: Manage Items
// GET  /admin/items/index.php   → list semua item
// POST /admin/items/index.php   → approve / reject / delete
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'admin');

$db = getDB();

// -------- POST: actions --------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $itemId = (int) ($_POST['item_id'] ?? 0);

    if ($itemId <= 0) jsonResponse(false, 'item_id tidak valid', [], 400);

    $item = $db->prepare('SELECT id, image FROM items WHERE id = ?');
    $item->execute([$itemId]);
    $item = $item->fetch();
    if (!$item) jsonResponse(false, 'Item tidak ditemukan', [], 404);

    switch ($action) {
        case 'approve':
            $db->prepare('UPDATE items SET is_approved = 1 WHERE id = ?')->execute([$itemId]);
            jsonResponse(true, 'Item disetujui dan sekarang tampil di marketplace.');

        case 'reject':
            $db->prepare('UPDATE items SET is_approved = 0, is_active = 0 WHERE id = ?')->execute([$itemId]);
            jsonResponse(true, 'Item ditolak dan disembunyikan dari marketplace.');

        case 'delete':
            // Cek apakah ada di order aktif
            $inOrder = $db->prepare("
                SELECT COUNT(*) FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE oi.item_id = ? AND o.status IN ('pending','paid')
            ");
            $inOrder->execute([$itemId]);
            if ((int)$inOrder->fetchColumn() > 0) {
                jsonResponse(false, 'Item tidak bisa dihapus, ada order aktif.', [], 409);
            }
            $uploadDir = __DIR__ . '/../../uploads/items/';
            if ($item['image'] && file_exists($uploadDir . $item['image'])) {
                unlink($uploadDir . $item['image']);
            }
            $db->prepare('DELETE FROM items WHERE id = ?')->execute([$itemId]);
            jsonResponse(true, 'Item berhasil dihapus.');

        default:
            jsonResponse(false, 'Action tidak dikenali', [], 400);
    }
}

// -------- GET: list items ------------------------------------
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$search   = sanitize($_GET['search']   ?? '');
$approved = $_GET['approved'] ?? '';   // '0' | '1' | ''
$catId    = (int) ($_GET['category']  ?? 0);

$where  = [];
$params = [];

if ($search) {
    $where[]  = 'i.name LIKE ?';
    $params[] = "%$search%";
}
if ($approved !== '') {
    $where[]  = 'i.is_approved = ?';
    $params[] = (int) $approved;
}
if ($catId) {
    $where[]  = 'i.category_id = ?';
    $params[] = $catId;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM items i $whereSQL");
$countStmt->execute($params);
$total    = (int) $countStmt->fetchColumn();
$lastPage = (int) ceil($total / $perPage);

$itemStmt = $db->prepare("
    SELECT i.id, i.name, i.slug, i.price, i.rarity, i.stock,
           i.is_approved, i.is_active, i.avg_rating, i.total_sold,
           i.created_at,
           c.name AS category_name,
           u.username AS seller_name
    FROM items i
    JOIN categories c ON c.id = i.category_id
    JOIN users u       ON u.id = i.seller_id
    $whereSQL
    ORDER BY i.is_approved ASC, i.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$itemStmt->execute($params);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $itemStmt->fetchAll(),
    'meta'    => ['total' => $total, 'page' => $page, 'last_page' => $lastPage],
]);

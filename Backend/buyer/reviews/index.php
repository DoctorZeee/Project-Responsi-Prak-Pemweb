<?php
// ============================================================
// CraftBazaar — Reviews: Get Reviews for Item
// GET /buyer/reviews/index.php?item_id=X
// Public (no login required)
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$db     = getDB();
$itemId = (int) ($_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    jsonResponse(false, 'item_id tidak valid', [], 400);
}

$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$countStmt = $db->prepare('SELECT COUNT(*) FROM reviews WHERE item_id = ?');
$countStmt->execute([$itemId]);
$total    = (int) $countStmt->fetchColumn();
$lastPage = (int) ceil($total / $perPage);

$stmt = $db->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at,
           u.username, u.avatar
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.item_id = ?
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute([$itemId]);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $stmt->fetchAll(),
    'meta'    => ['total' => $total, 'page' => $page, 'last_page' => $lastPage],
]);

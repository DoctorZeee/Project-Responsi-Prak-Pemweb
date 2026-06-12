<?php
// ============================================================
// CraftBazaar — Public: Item Detail
// GET /api/item_detail.php?id=xxx
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$db   = getDB();
// UBAH: Menerima 'id' sebagai angka (integer), bukan slug
$id   = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID item wajib diisi']);
    exit;
}

// UBAH: Query sekarang mencari berdasarkan i.id = ?
$stmt = $db->prepare("
    SELECT i.id, i.name, i.slug, i.description, i.price, i.rarity,
           i.image, i.stock, i.avg_rating, i.total_sold, i.created_at,
           c.name AS category_name, c.slug AS category_slug,
           u.id AS seller_id, u.username AS seller_name, u.avatar AS seller_avatar, u.bio AS seller_bio
    FROM items i
    JOIN categories c ON c.id = i.category_id
    JOIN users u       ON u.id = i.seller_id
    WHERE i.id = ? AND i.is_approved = 1 AND i.is_active = 1
    LIMIT 1
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
    exit;
}

// Ambil 5 review terbaru
$reviews = $db->prepare("
    SELECT r.rating, r.comment, r.created_at, u.username, u.avatar
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.item_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reviews->execute([$item['id']]);

// Rating summary
$ratingSummary = $db->prepare("
    SELECT rating, COUNT(*) AS count
    FROM reviews WHERE item_id = ?
    GROUP BY rating ORDER BY rating DESC
");
$ratingSummary->execute([$item['id']]);

header('Content-Type: application/json');
echo json_encode([
    'success'        => true,
    'data'           => $item,
    'reviews'        => $reviews->fetchAll(),
    'rating_summary' => $ratingSummary->fetchAll(),
]);
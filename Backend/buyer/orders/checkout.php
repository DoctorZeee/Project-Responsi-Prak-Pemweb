<?php
// ============================================================
// CraftBazaar — Order: Checkout
// POST /Backend/buyer/orders/checkout.php
// FIX: tambah kolom payment_proof ke INSERT
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed', [], 405);

$db     = getDB();
$userId = currentUser()['id'];
$note   = sanitize($_POST['note'] ?? '');

// Validasi file upload (opsional)
$fileName   = null;
$uploadDir  = __DIR__ . '/../../uploads/payments/';

if (!empty($_FILES['payment_proof']['name'])) {
    if ($_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'Gagal mengunggah bukti transfer.', [], 400);
    }
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $ext       = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'])) jsonResponse(false, 'Format bukti hanya JPG/PNG.', [], 400);

    $fileName      = time() . '_' . uniqid() . '.' . $ext;
    $targetPath    = $uploadDir . $fileName;
    if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
        jsonResponse(false, 'Gagal menyimpan bukti transfer.', [], 500);
    }
}

// Ambil cart
$cartStmt = $db->prepare("
    SELECT c.id AS cart_id, c.quantity,
           i.id AS item_id, i.seller_id, i.name,
           i.price, i.stock, i.is_active, i.is_approved
    FROM cart c JOIN items i ON i.id = c.item_id
    WHERE c.user_id = ?
");
$cartStmt->execute([$userId]);
$cartItems = $cartStmt->fetchAll();

if (empty($cartItems)) {
    if ($fileName) @unlink($uploadDir . $fileName);
    jsonResponse(false, 'Keranjang belanja kosong.', [], 400);
}

$total = 0;
foreach ($cartItems as $ci) {
    if (!$ci['is_active'] || !$ci['is_approved']) {
        if ($fileName) @unlink($uploadDir . $fileName);
        jsonResponse(false, "Item \"{$ci['name']}\" tidak tersedia lagi.", [], 400);
    }
    if ($ci['quantity'] > $ci['stock']) {
        if ($fileName) @unlink($uploadDir . $fileName);
        jsonResponse(false, "Stok \"{$ci['name']}\" tidak cukup. Tersisa: {$ci['stock']}", [], 400);
    }
    $total += $ci['price'] * $ci['quantity'];
}

$db->beginTransaction();
try {
    $orderStmt = $db->prepare('INSERT INTO orders (buyer_id, total_price, status, note, payment_proof) VALUES (?, ?, \'pending\', ?, ?)');
    $orderStmt->execute([$userId, $total, $note, $fileName]);
    $orderId = (int)$db->lastInsertId();

    $oiStmt    = $db->prepare('INSERT INTO order_items (order_id, item_id, seller_id, quantity, price) VALUES (?, ?, ?, ?, ?)');
    $stockStmt = $db->prepare('UPDATE items SET stock = stock - ?, total_sold = total_sold + ? WHERE id = ?');

    foreach ($cartItems as $ci) {
        $oiStmt->execute([$orderId, $ci['item_id'], $ci['seller_id'], $ci['quantity'], $ci['price']]);
        $stockStmt->execute([$ci['quantity'], $ci['quantity'], $ci['item_id']]);
    }

    $db->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);
    $db->commit();

    jsonResponse(true, 'Checkout berhasil! Menunggu verifikasi.', [
        'order_id'    => $orderId,
        'total_price' => $total,
    ]);
} catch (Exception $e) {
    $db->rollBack();
    if ($fileName) @unlink($uploadDir . $fileName);
    jsonResponse(false, 'Terjadi kesalahan sistem saat checkout.', [], 500);
}
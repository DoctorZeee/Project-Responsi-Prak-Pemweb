<?php
// ============================================================
// CraftBazaar — Seller: Update Item
// POST /seller/items/update.php
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'seller', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db = getDB();

$itemId      = (int)    ($_POST['id']           ?? 0);
$name        = sanitize($_POST['name']          ?? '');
$description = sanitize($_POST['description']   ?? '');
$price       = (float)  ($_POST['price']         ?? 0);
$category_id = (int)    ($_POST['category_id']   ?? 0);
$rarity      = sanitize($_POST['rarity']         ?? 'common');
$stock       = (int)    ($_POST['stock']          ?? 1);
$is_active   = (int)    ($_POST['is_active']      ?? 1);

if ($itemId <= 0) {
    jsonResponse(false, 'ID item tidak valid', [], 400);
}

// Ambil item — pastikan milik seller ini
$sellerId = currentUser()['id'];
$checkSQL = isAdmin()
    ? 'SELECT id, image, slug FROM items WHERE id = ?'
    : 'SELECT id, image, slug FROM items WHERE id = ? AND seller_id = ?';
$checkParams = isAdmin() ? [$itemId] : [$itemId, $sellerId];

$existing = $db->prepare($checkSQL);
$existing->execute($checkParams);
$item = $existing->fetch();

if (!$item) {
    jsonResponse(false, 'Item tidak ditemukan atau bukan milik kamu', [], 404);
}

// Validasi
$errors = [];
if (empty($name))  $errors[] = 'Nama item wajib diisi';
if ($price <= 0)   $errors[] = 'Harga harus lebih dari 0';
if ($category_id <= 0) $errors[] = 'Kategori wajib dipilih';
if (!in_array($rarity, ['common','uncommon','rare','epic','legendary']))
                   $errors[] = 'Rarity tidak valid';

if (!empty($errors)) {
    jsonResponse(false, implode(', ', $errors), ['errors' => $errors], 422);
}

// Handle gambar baru
$imageName = $item['image'];
if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/../../uploads/items/';
    $newImage  = uploadItemImage($_FILES['image'], $uploadDir);
    if (!$newImage) {
        jsonResponse(false, 'Gagal upload gambar baru. Format harus JPG/PNG/WEBP, max 2MB.', [], 422);
    }
    // Hapus gambar lama
    if ($imageName && file_exists($uploadDir . $imageName)) {
        unlink($uploadDir . $imageName);
    }
    $imageName = $newImage;
}

$slug = uniqueSlug($db, $name, $itemId);

$db->prepare('
    UPDATE items
    SET name = ?, slug = ?, description = ?, price = ?, category_id = ?,
        rarity = ?, stock = ?, image = ?, is_active = ?,
        is_approved = 0
    WHERE id = ?
')->execute([$name, $slug, $description, $price, $category_id, $rarity, $stock, $imageName, $is_active, $itemId]);

jsonResponse(true, 'Item berhasil diupdate. Menunggu persetujuan admin kembali.', [
    'item_id' => $itemId,
    'slug'    => $slug,
]);

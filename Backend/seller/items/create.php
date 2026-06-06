<?php
// ============================================================
// CraftBazaar — Seller: Create Item
// POST /seller/items/create.php
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'seller', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

$db = getDB();

$name        = sanitize($_POST['name']        ?? '');
$description = sanitize($_POST['description'] ?? '');
$price       = (float)  ($_POST['price']      ?? 0);
$category_id = (int)    ($_POST['category_id'] ?? 0);
$rarity      = sanitize($_POST['rarity']       ?? 'common');
$stock       = (int)    ($_POST['stock']        ?? 1);

// Validasi
$errors = [];
if (empty($name))                              $errors[] = 'Nama item wajib diisi';
if ($price <= 0)                               $errors[] = 'Harga harus lebih dari 0';
if ($category_id <= 0)                         $errors[] = 'Kategori wajib dipilih';
if (!in_array($rarity, ['common','uncommon','rare','epic','legendary']))
                                               $errors[] = 'Rarity tidak valid';
if ($stock < 0)                                $errors[] = 'Stok tidak boleh negatif';

if (!empty($errors)) {
    jsonResponse(false, implode(', ', $errors), ['errors' => $errors], 422);
}

// Cek kategori ada
$catCheck = $db->prepare('SELECT id FROM categories WHERE id = ?');
$catCheck->execute([$category_id]);
if (!$catCheck->fetch()) {
    jsonResponse(false, 'Kategori tidak ditemukan', [], 404);
}

// Upload gambar
$imageName = null;
if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/../../uploads/items/';
    $imageName = uploadItemImage($_FILES['image'], $uploadDir);
    if (!$imageName) {
        jsonResponse(false, 'Gagal upload gambar. Format harus JPG/PNG/WEBP, max 2MB.', [], 422);
    }
}

$sellerId = currentUser()['id'];
$slug     = uniqueSlug($db, $name);

$stmt = $db->prepare('
    INSERT INTO items (seller_id, category_id, name, slug, description, price, rarity, stock, image, is_approved)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
');
$stmt->execute([$sellerId, $category_id, $name, $slug, $description, $price, $rarity, $stock, $imageName]);

$newId = $db->lastInsertId();

jsonResponse(true, 'Item berhasil ditambahkan dan menunggu persetujuan admin.', [
    'item_id' => (int) $newId,
    'slug'    => $slug,
], 201);

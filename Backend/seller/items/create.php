<?php
// ============================================================
// CraftBazaar — Seller: Create Item
// POST /Backend/seller/items/create.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('seller', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed', [], 405);

$db          = getDB();
$name        = sanitize($_POST['name']        ?? '');
$description = sanitize($_POST['description'] ?? '');
$price       = (float) ($_POST['price']       ?? 0);
$category_id = (int)   ($_POST['category_id'] ?? 0);
$rarity      = sanitize($_POST['rarity']      ?? 'common');
$stock       = (int)   ($_POST['stock']       ?? 1);

$errors = [];
if (empty($name))                                                    $errors[] = 'Nama item wajib diisi';
if ($price <= 0)                                                     $errors[] = 'Harga harus lebih dari 0';
if ($category_id <= 0)                                               $errors[] = 'Kategori wajib dipilih';
if (!in_array($rarity, ['common','uncommon','rare','epic','legendary'])) $errors[] = 'Rarity tidak valid';
if ($stock < 0)                                                      $errors[] = 'Stok tidak boleh negatif';

if (!empty($errors)) jsonResponse(false, implode(', ', $errors), ['errors' => $errors], 422);

$catCheck = $db->prepare('SELECT id FROM categories WHERE id = ?');
$catCheck->execute([$category_id]);
if (!$catCheck->fetch()) jsonResponse(false, 'Kategori tidak ditemukan', [], 404);

$imageName = null;
if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/../../uploads/items/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $imageName = uploadItemImage($_FILES['image'], $uploadDir);
    if (!$imageName) jsonResponse(false, 'Gagal upload gambar. Format harus JPG/PNG/WEBP, max 2MB.', [], 422);
}

$sellerId = currentUser()['id'];
$slug     = uniqueSlug($db, $name);
// Admin yang upload langsung approved; seller butuh persetujuan
$isApproved = isAdmin() ? 1 : 0;

$stmt = $db->prepare('INSERT INTO items (seller_id, category_id, name, slug, description, price, rarity, stock, image, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$sellerId, $category_id, $name, $slug, $description, $price, $rarity, $stock, $imageName, $isApproved]);

jsonResponse(true, 'Item berhasil ditambahkan' . ($isApproved ? '.' : ' dan menunggu persetujuan admin.'), [
    'item_id' => (int)$db->lastInsertId(),
    'slug'    => $slug,
], 201);
<?php
// ============================================================
// CraftBazaar — Seller: Update Item
// POST /Backend/seller/items/update.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('seller', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed', [], 405);

$db       = getDB();
$sellerId = currentUser()['id'];
$itemId   = (int)($_POST['item_id'] ?? 0);

if ($itemId <= 0) jsonResponse(false, 'item_id wajib diisi', [], 400);

// Cek kepemilikan (admin boleh update semua)
$check = $db->prepare('SELECT id FROM items WHERE id = ?' . (isAdmin() ? '' : ' AND seller_id = ?'));
$args  = isAdmin() ? [$itemId] : [$itemId, $sellerId];
$check->execute($args);
if (!$check->fetch()) jsonResponse(false, 'Item tidak ditemukan atau bukan milik Anda', [], 404);

$name        = sanitize($_POST['name']        ?? '');
$description = sanitize($_POST['description'] ?? '');
$price       = (float) ($_POST['price']       ?? 0);
$stock       = (int)   ($_POST['stock']       ?? 0);
$rarity      = sanitize($_POST['rarity']      ?? '');
$category_id = (int)   ($_POST['category_id'] ?? 0);

$errors = [];
if (empty($name))  $errors[] = 'Nama item wajib diisi';
if ($price <= 0)   $errors[] = 'Harga harus lebih dari 0';
if ($stock < 0)    $errors[] = 'Stok tidak boleh negatif';
if (!in_array($rarity, ['common','uncommon','rare','epic','legendary'])) $errors[] = 'Rarity tidak valid';

if (!empty($errors)) jsonResponse(false, implode(', ', $errors), ['errors' => $errors], 422);

$slug = uniqueSlug($db, $name, $itemId);

$db->prepare('UPDATE items SET name=?, slug=?, description=?, price=?, stock=?, rarity=?, category_id=?, updated_at=NOW() WHERE id=?')
   ->execute([$name, $slug, $description, $price, $stock, $rarity, $category_id ?: null, $itemId]);

jsonResponse(true, 'Item berhasil diperbarui.', ['item_id' => $itemId, 'slug' => $slug]);
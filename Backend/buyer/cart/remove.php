<?php
// ============================================================
// CraftBazaar — Cart: Remove Item
// POST /Backend/buyer/cart/remove.php
// ============================================================
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
requireRoleApi('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed', [], 405);

$db       = getDB();
$userId   = currentUser()['id'];
$cartId   = (int)($_POST['cart_id'] ?? 0);

if ($cartId <= 0) jsonResponse(false, 'cart_id wajib diisi', [], 400);

$stmt = $db->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
$stmt->execute([$cartId, $userId]);

if ($stmt->rowCount() === 0) jsonResponse(false, 'Item tidak ditemukan di cart', [], 404);

$countStmt = $db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?');
$countStmt->execute([$userId]);

jsonResponse(true, 'Item dihapus dari cart.', ['cart_count' => (int)$countStmt->fetchColumn()]);
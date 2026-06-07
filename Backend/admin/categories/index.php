<?php
// ============================================================
// CraftBazaar — Admin: Manage Categories
// GET  /admin/categories/index.php   → list
// POST /admin/categories/index.php   → create / update / delete
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole('/auth/login.php', 'admin');

$db = getDB();

// -------- POST: CRUD -----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    switch ($action) {

        case 'create':
            $name = sanitize($_POST['name'] ?? '');
            $icon = sanitize($_POST['icon'] ?? '');
            if (empty($name)) jsonResponse(false, 'Nama kategori wajib diisi', [], 422);

            $slug = makeSlug($name);

            // Cek duplikat
            $dup = $db->prepare('SELECT id FROM categories WHERE slug = ?');
            $dup->execute([$slug]);
            if ($dup->fetch()) jsonResponse(false, 'Kategori sudah ada', [], 409);

            $db->prepare('INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)')
               ->execute([$name, $slug, $icon ?: null]);

            jsonResponse(true, 'Kategori berhasil ditambahkan.', [
                'category_id' => (int) $db->lastInsertId(),
                'slug'        => $slug,
            ], 201);

        case 'update':
            $catId = (int) ($_POST['id'] ?? 0);
            $name  = sanitize($_POST['name'] ?? '');
            $icon  = sanitize($_POST['icon'] ?? '');

            if ($catId <= 0 || empty($name)) jsonResponse(false, 'Data tidak valid', [], 400);

            $slug = makeSlug($name);

            // Cek duplikat (exclude self)
            $dup = $db->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
            $dup->execute([$slug, $catId]);
            if ($dup->fetch()) jsonResponse(false, 'Nama kategori sudah digunakan', [], 409);

            $db->prepare('UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?')
               ->execute([$name, $slug, $icon ?: null, $catId]);

            jsonResponse(true, 'Kategori berhasil diperbarui.');

        case 'delete':
            $catId = (int) ($_POST['id'] ?? 0);
            if ($catId <= 0) jsonResponse(false, 'ID tidak valid', [], 400);

            // Cek ada item yang pakai kategori ini
            $usedBy = $db->prepare('SELECT COUNT(*) FROM items WHERE category_id = ?');
            $usedBy->execute([$catId]);
            if ((int)$usedBy->fetchColumn() > 0) {
                jsonResponse(false, 'Kategori tidak bisa dihapus karena masih digunakan oleh item.', [], 409);
            }

            $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$catId]);
            jsonResponse(true, 'Kategori berhasil dihapus.');

        default:
            jsonResponse(false, 'Action tidak dikenali', [], 400);
    }
}

// -------- GET: list ------------------------------------------
$stmt = $db->prepare("
    SELECT c.id, c.name, c.slug, c.icon, c.created_at,
           COUNT(i.id) AS item_count
    FROM categories c
    LEFT JOIN items i ON i.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC
");
$stmt->execute();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $stmt->fetchAll(),
]);

<?php
// ============================================================
// CraftBazaar — Admin: Manage Users
// GET  /admin/users/index.php          → list users
// POST /admin/users/index.php          → toggle active / change role
// ============================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_helper.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRoleApi('admin');

$db = getDB();

// -------- POST: update user ----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $uid    = (int) ($_POST['user_id'] ?? 0);

    if ($uid <= 0) jsonResponse(false, 'user_id tidak valid', [], 400);

    // Jangan ubah akun admin sendiri
    if ($uid === (int) currentUser()['id']) {
        jsonResponse(false, 'Tidak bisa mengubah akun sendiri.', [], 400);
    }

    $user = $db->prepare('SELECT id, role, is_active FROM users WHERE id = ?');
    $user->execute([$uid]);
    $user = $user->fetch();
    if (!$user) jsonResponse(false, 'User tidak ditemukan', [], 404);

    switch ($action) {
        case 'toggle_active':
            $newActive = $user['is_active'] ? 0 : 1;
            $db->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$newActive, $uid]);
            jsonResponse(true, 'Status akun diperbarui.', ['is_active' => $newActive]);

        case 'change_role':
            $newRole = sanitize($_POST['role'] ?? '');
            if (!in_array($newRole, ['buyer', 'seller', 'admin'])) {
                jsonResponse(false, 'Role tidak valid', [], 400);
            }
            $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $uid]);
            jsonResponse(true, 'Role user diperbarui ke ' . $newRole);

        case 'delete':
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            jsonResponse(true, 'User berhasil dihapus.');

        default:
            jsonResponse(false, 'Action tidak dikenali', [], 400);
    }
}

// -------- GET: list users ------------------------------------
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$search  = sanitize($_GET['search'] ?? '');
$role    = sanitize($_GET['role']   ?? '');

$where   = [];
$params  = [];

if ($search) {
    $where[]  = '(username LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (in_array($role, ['buyer', 'seller', 'admin'])) {
    $where[]  = 'role = ?';
    $params[] = $role;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM users $whereSQL");
$countStmt->execute($params);
$total    = (int) $countStmt->fetchColumn();
$lastPage = (int) ceil($total / $perPage);

$userStmt = $db->prepare("
    SELECT id, username, email, role, balance, is_active, created_at
    FROM users
    $whereSQL
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
");
$userStmt->execute($params);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data'    => $userStmt->fetchAll(),
    'meta'    => ['total' => $total, 'page' => $page, 'last_page' => $lastPage],
]);

<?php
// ============================================================
// CraftBazaar — Logout Handler
// GET /Backend/auth/logout.php
// ============================================================
require_once __DIR__ . '/../includes/auth_helper.php';

header('Content-Type: application/json');
destroySession();

echo json_encode(['success' => true, 'message' => 'Berhasil keluar.']);
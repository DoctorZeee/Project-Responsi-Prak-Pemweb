<?php
// ============================================================
// CraftBazaar — Logout Handler
// GET /auth/logout.php
// ============================================================

require_once __DIR__ . '/../includes/auth_helper.php';

destroySession();

header('Location: /auth/login.php');
exit;

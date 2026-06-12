<?php
// ============================================================
// CraftBazaar — General Helpers
// ============================================================

function makeSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uniqueSlug(PDO $db, string $text, ?int $excludeId = null): string {
    $base = makeSlug($text);
    $slug = $base;
    $i    = 1;
    while (true) {
        $sql  = 'SELECT id FROM items WHERE slug = ?';
        $args = [$slug];
        if ($excludeId !== null) { $sql .= ' AND id != ?'; $args[] = $excludeId; }
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function uploadItemImage(array $file, string $uploadDir): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowed)) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null;
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('item_', true) . '.' . $ext;
    $dest     = rtrim($uploadDir, '/') . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $filename;
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)));
}

function jsonResponse(bool $success, string $message, array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function recalcAvgRating(PDO $db, int $itemId): void {
    $stmt = $db->prepare('SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM reviews WHERE item_id = ?');
    $stmt->execute([$itemId]);
    $row  = $stmt->fetch();
    $avg  = $row['total'] > 0 ? round((float)$row['avg_r'], 2) : 0.00;
    $db->prepare('UPDATE items SET avg_rating = ? WHERE id = ?')->execute([$avg, $itemId]);
}

function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
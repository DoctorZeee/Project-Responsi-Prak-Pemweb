<?php
// ============================================================
// CraftBazaar — Database Configuration
// ============================================================

function loadEnv(): void {
    static $loaded = false;
    if ($loaded) return;

    $envFile = dirname(__DIR__) . '/.env';
    if (!file_exists($envFile)) {
        die(json_encode(['success' => false, 'message' => 'File .env tidak ditemukan.']));
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!empty($key)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    $loaded = true;
}

function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        loadEnv();
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
            env('DB_HOST', 'localhost'),
            env('DB_NAME', 'craftbazaar'),
            env('DB_CHARSET', 'utf8mb4')
        );
        try {
            $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// CORS headers — izinkan request dari Frontend folder
function setCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
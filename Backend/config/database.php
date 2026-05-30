<?php
// ============================================================
// CraftBazaar — Database Configuration
// Baca kredensial dari file .env di root project
// ============================================================

function loadEnv(): void {
    static $loaded = false;
    if ($loaded) return;

    // Cari file .env dari root project (2 level ke atas dari /config/)
    $envFile = dirname(__DIR__) . '/.env';

    if (!file_exists($envFile)) {
        die(json_encode([
            'success' => false,
            'message' => 'File .env tidak ditemukan. Copy .env.example ke .env dan isi konfigurasinya.'
        ]));
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip baris komentar
        if (str_starts_with(trim($line), '#')) continue;

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

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            env('DB_HOST', 'localhost'),
            env('DB_NAME', 'craftbazaar'),
            env('DB_CHARSET', 'utf8mb4')
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]));
        }
    }

    return $pdo;
}

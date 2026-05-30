# CraftBazaar — Backend Setup Guide

## 📁 Struktur Folder

```
Project-Responsi-Prak-Pemweb/
├── config/
│   ├── database.php       ← koneksi PDO + fungsi getDB()
│   └── schema.sql         ← semua tabel + seed data
├── includes/
│   └── auth_helper.php    ← session, guard, role helper
├── auth/
│   ├── login.php          ← POST handler login
│   ├── register.php       ← POST handler register
│   └── logout.php         ← destroy session + redirect
├── uploads/
│   └── items/             ← folder upload gambar item
├── generate_hash.php      ← tool generate bcrypt (HAPUS saat deploy)
└── ...
```

---

## ⚙️ Setup di Ubuntu + Nginx

### 1. Install MySQL & buat database

```bash
sudo mysql -u root -p
```

```sql
CREATE USER 'craftuser'@'localhost' IDENTIFIED BY 'craftpass123';
GRANT ALL PRIVILEGES ON craftbazaar.* TO 'craftuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Import schema

```bash
mysql -u craftuser -p craftbazaar < config/schema.sql
```

### 3. Sesuaikan config/database.php

```php
define('DB_USER', 'craftuser');
define('DB_PASS', 'craftpass123');
```

### 4. Permission folder upload

```bash
sudo chown -R www-data:www-data uploads/
sudo chmod -R 775 uploads/
```

### 5. Nginx config (tambahkan di server block)

```nginx
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
}
```

---

## 🧪 Test Akun Seed Data

| Username      | Password     | Role   |
|---------------|-------------|--------|
| admin         | password    | admin  |
| steve_builder | password    | seller |
| alex_crafter  | password    | seller |
| notch_fan     | password    | buyer  |
| herobrine99   | password    | buyer  |

> ⚠️ Hash di schema.sql menggunakan password `password` (Laravel default hash).
> Untuk password lain, jalankan `/generate_hash.php?p=passwordbaru` lalu update manual di DB.

---

## 🔌 Cara Pakai Auth Helper

```php
<?php
require_once __DIR__ . '/includes/auth_helper.php';

// Cek login
if (!isLoggedIn()) { ... }

// Ambil user aktif
$user = currentUser(); // ['id', 'username', 'email', 'role']

// Guard halaman — redirect kalau belum login
requireLogin();

// Guard role — hanya seller & admin
requireRole('/', 'seller', 'admin');

// Cek role spesifik
if (isAdmin()) { ... }
if (isSeller()) { ... }
```

---

## ✅ Checklist BE-01 & BE-02

- [x] Create database
- [x] Create users table
- [x] Create items table
- [x] Create orders table
- [x] Create reviews table
- [x] Create categories table
- [x] Seed data (categories + dummy users + dummy items)
- [x] Register logic + validasi
- [x] Login logic (username atau email)
- [x] Password hashing (bcrypt)
- [x] Session handling
- [x] Logout system
- [x] Role redirect (admin/seller/buyer)
- [x] Route protection (requireLogin, requireRole)

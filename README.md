# Responsi Praktikum Pemrograman Web

Repositori ini berisi kumpulan tugas dan responsi praktikum mata kuliah Pemrograman Web. Project dibuat menggunakan teknologi web dasar tanpa framework agar lebih fokus pada pemahaman konsep fundamental pengembangan website.

---

## Teknologi yang Digunakan

- HTML5
- CSS3
- JavaScript
- PHP Native
- MySQL

---

## Struktur Direktori

```bash
.
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── config.php
│
├── database/
│   └── database.sql
│
├── index.php
└── README.md
```

---

## Menjalankan Project

### Clone Repository

```bash
git clone https://github.com/username/nama-repo.git
```

### Masuk ke Folder Project

```bash
cd nama-repo
```

### Jalankan di Local Server

Project dapat dijalankan menggunakan:

- Laragon (Windows)
- Nginx + PHP (Linux Ubuntu)

Letakkan folder project pada direktori web server masing-masing.

---

## Akses Project

Buka browser lalu akses:

```txt
http://localhost/nama-repo/
```

---

## Konfigurasi Database

Jika menggunakan database:

1. Buat database baru melalui phpMyAdmin
2. Import file `.sql` dari folder `database`
3. Sesuaikan konfigurasi koneksi pada file:

```php
includes/config.php
```

Contoh konfigurasi:

```php
<?php
$conn = mysqli_connect("localhost", "root", "", "nama_database");
?>
```

---

## Catatan

- Tidak menggunakan framework tambahan
- Seluruh project dibuat menggunakan PHP Native
- Struktur project dapat berubah sesuai kebutuhan praktikum
- Dibuat untuk keperluan pembelajaran dan pengembangan dasar web

---

## Lisensi

Repositori ini digunakan untuk keperluan akademik dan pembelajaran.
Silakan digunakan sebagai referensi belajar.

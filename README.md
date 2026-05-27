# 🛠️ GitHub Workflow & Collaboration Guide

Panduan ini digunakan agar proses development berjalan rapi, aman, dan tidak merusak branch utama (`main`).  
Seluruh anggota tim diwajibkan mengikuti alur kerja berikut sebelum melakukan perubahan pada project.

---

# 🌿 Branch Structure

Repository menggunakan struktur branch berikut:

```bash
main
develop
frontend
backend
```

## Penjelasan

| Branch | Fungsi |
|---|---|
| `main` | Branch utama dan versi stabil project |
| `develop` | Tempat penggabungan seluruh fitur sebelum masuk ke `main` |
| `frontend` | Development frontend |
| `backend` | Development backend |

---

# ⚠️ Aturan Penting

- Jangan commit langsung ke `main`
- Gunakan branch masing-masing
- Selalu lakukan `git pull` atau `git fetch` sebelum bekerja
- Pastikan project berjalan normal sebelum melakukan push
- Gunakan commit message yang jelas

---

# 📥 Clone Repository

Clone repository terlebih dahulu:

```bash
git clone https://github.com/username/nama-repo.git
```

Masuk ke folder project:

```bash
cd nama-repo
```

---

# 🔍 Cek Branch

Lihat branch yang tersedia:

```bash
git branch
```

Lihat seluruh branch remote:

```bash
git branch -a
```

---

# 🔄 Ambil Update Terbaru

Sebelum mulai coding, selalu ambil update terbaru dari repository.

## Mengambil informasi update terbaru

```bash
git fetch
```

## Mengambil sekaligus mengupdate branch aktif

```bash
git pull origin develop
```

---

# 🌿 Pindah Branch

## Pindah ke branch develop

```bash
git checkout develop
```

## Pindah ke branch frontend

```bash
git checkout frontend
```

## Pindah ke branch backend

```bash
git checkout backend
```

---

# 🌱 Membuat Branch Baru

Jika ingin membuat branch fitur baru:

```bash
git checkout -b feature/nama-fitur
```

Contoh:

```bash
git checkout -b feature/login-system
```

---

# 📊 Melihat Status Perubahan

Cek file yang berubah:

```bash
git status
```

---

# ➕ Menambahkan File ke Commit

Menambahkan semua perubahan:

```bash
git add .
```

Menambahkan file tertentu:

```bash
git add nama_file
```

---

# 💾 Commit Perubahan

Lakukan commit dengan pesan yang jelas.

Contoh:

```bash
git commit -m "Membuat halaman marketplace"
```

Contoh lain:

```bash
git commit -m "Menambahkan sistem login"
```

---

# 🚀 Push Perubahan

Push ke branch masing-masing.

## Frontend

```bash
git push origin frontend
```

## Backend

```bash
git push origin backend
```

## Feature Branch

```bash
git push origin feature/nama-fitur
```

---

# 🔀 Pull Request (PR)

Setelah fitur selesai:

1. Push branch
2. Buka GitHub repository
3. Klik:
   ```txt
   Compare & pull request
   ```
4. Buat Pull Request menuju:
   ```txt
   develop
   ```
5. Setelah dicek dan aman, baru merge

---

# ⚠️ Jangan Langsung Merge ke Main

Alur yang benar:

```bash
feature branch
    ↓
develop
    ↓
main
```

Branch `main` hanya digunakan untuk:
- versi final
- demo
- deploy
- presentasi

---

# 🛑 Menghindari Conflict

Sebelum push:

```bash
git fetch
git pull origin develop
```

Tujuannya agar branch tetap sinkron dan mengurangi conflict.

---

# 🔥 Jika Terjadi Conflict

1. Buka file yang conflict
2. Perbaiki bagian conflict
3. Simpan file
4. Jalankan:

```bash
git add .
git commit -m "Resolve merge conflict"
```

---

# 📋 Workflow Development yang Direkomendasikan

## 1. Ambil update terbaru

```bash
git fetch
git pull origin develop
```

## 2. Pindah branch kerja

```bash
git checkout frontend
```

atau

```bash
git checkout backend
```

## 3. Coding

## 4. Cek status

```bash
git status
```

## 5. Add dan commit

```bash
git add .
git commit -m "Pesan commit"
```

## 6. Push

```bash
git push origin frontend
```

## 7. Buat Pull Request ke develop

---

# 🧹 Commit Message yang Baik

## Contoh yang baik

```bash
git commit -m "Membuat halaman login"
```

```bash
git commit -m "Menambahkan CRUD item"
```

```bash
git commit -m "Memperbaiki responsive navbar"
```

---

## Contoh yang buruk

```bash
git commit -m "fix"
```

```bash
git commit -m "update"
```

```bash
git commit -m "baru"
```

---

# 📌 Tips Collaboration

- Komunikasikan fitur yang sedang dikerjakan
- Jangan mengedit file yang sama secara bersamaan
- Gunakan issue dan project board GitHub
- Update progress secara berkala
- Gunakan branch sesuai divisi

---

# 🛡️ Safe Git Commands

## Aman digunakan

```bash
git status
git fetch
git pull
git add .
git commit
git push
```

---

## Hati-hati menggunakan

```bash
git reset --hard
git push --force
git clean -fd
```

Karena dapat menghapus perubahan secara permanen.

---

# Responsi Praktikum Pemrograman Web

Repositori ini berisi kumpulan tugas dan responsi praktikum mata kuliah Pemrograman Web. Project dibuat menggunakan teknologi web dasar tanpa framework agar lebih fokus pada pemahaman konsep fundamental pengembangan website.

---

# 📦 Teknologi yang Digunakan

- HTML5
- CSS3
- JavaScript
- PHP Native
- MySQL

---

# 📁 Struktur Direktori

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

# ▶️ Menjalankan Project

## Clone Repository

```bash
git clone https://github.com/username/nama-repo.git
```

## Masuk ke Folder Project

```bash
cd nama-repo
```

## Jalankan di Local Server

Project dapat dijalankan menggunakan:

- Laragon (Windows)
- Nginx + PHP (Linux Ubuntu)

Letakkan folder project pada direktori web server masing-masing.

---

# 🌐 Akses Project

Buka browser lalu akses:

```txt
http://localhost/nama-repo/
```

---

# 🗄️ Konfigurasi Database

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

# 📝 Catatan

- Tidak menggunakan framework tambahan
- Seluruh project dibuat menggunakan PHP Native
- Struktur project dapat berubah sesuai kebutuhan praktikum
- Dibuat untuk keperluan pembelajaran dan pengembangan dasar web

---

# 📄 Lisensi

Repositori ini digunakan untuk keperluan akademik dan pembelajaran.  
Silakan digunakan sebagai referensi belajar.

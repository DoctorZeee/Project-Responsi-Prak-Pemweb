sql
-- ============================================================
-- CRAFTBAZAAR DATABASE
-- VPS / DEDICATED SERVER VERSION
-- ============================================================

DROP DATABASE IF EXISTS craftbazaar;

CREATE DATABASE craftbazaar
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE craftbazaar;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;
```

Lalu sisanya gunakan file SQL yang sudah Anda kirim mulai dari:

```sql
CREATE TABLE users (
...
```

hingga bagian akhir:

```sql
SET FOREIGN_KEY_CHECKS = 1;
```

Saya juga menyarankan beberapa perubahan kecil agar lebih cocok untuk VPS production:

1.

```sql
CREATE DATABASE craftbazaar
```

ubah menjadi

```sql
CREATE DATABASE IF NOT EXISTS craftbazaar
```

2.

Semua

```sql
INSERT IGNORE INTO
```

boleh diubah menjadi

```sql
INSERT INTO
```

karena database baru akan selalu kosong.

3.

Tambahkan di bagian paling atas:

```sql
SET NAMES utf8mb4;
SET time_zone = '+00:00';
```

sehingga awal file menjadi:

```sql
SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP DATABASE IF EXISTS craftbazaar;

CREATE DATABASE IF NOT EXISTS craftbazaar
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE craftbazaar;

SET FOREIGN_KEY_CHECKS=0;



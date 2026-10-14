# 🗺️ Sales Territory Optimizer — Telkom Madiun

Sistem Alokasi Sales Berdasarkan Potensi Wilayah Madiun. Menggunakan **Haversine distance** untuk perhitungan jarak dan **Integer Linear Programming (ILP)** untuk optimasi penugasan pelanggan ke sales dengan memperhatikan kapasitas masing-masing.

> **Stack:** PHP 7.4+ / MySQL 5.7+ / Vanilla JS / Leaflet.js / Chart.js

---

## ✨ Fitur Utama

| # | Halaman | Fitur |
|---|---------|-------|
| 1 | **Login** | Split-screen dengan branding Telkom, form autentikasi + SSO button |
| 2 | **Lupa Password** | Halaman reset password terpusat |
| 3 | **Dashboard** | 4 stat cards, 4 chart (bar, stacked, doughnut, utilisasi), riwayat optimasi |
| 4 | **Data Sales** | CRUD lengkap, modal dengan peta lokasi, filter, pagination, CSV export |
| 5 | **Data Pelanggan** | CRUD lengkap, filter potensi/wilayah/sales, pagination, CSV export |
| 6 | **Peta Potensi Wilayah** | Leaflet map, marker pelanggan & sales, popup, heatmap toggle, filter |
| 7 | **Parameter Alokasi** | Konfigurasi bobot jarak/potensi, radius, mode solver, rumus skor |
| 8 | **Perhitungan Haversine** | Matriks jarak pelanggan×sales, highlight min/max, statistik |
| 9 | **Optimasi ILP** | Konfigurasi solver, jalankan optimasi, model matematis, riwayat run |
| 10 | **Hasil Alokasi** | Hasil per sales, chart distribusi, total jarak, ekspor hasil |
| 11 | **Detail Sales** | Profil sales, jarak ke pelanggan, peta cakupan, list pelanggan |
| 12 | **Laporan** | Overview, ringkasan performa, riwayat run, print & export |
| 13 | **Manajemen Pengguna** | CRUD pengguna, peran (Admin/Manajer/Operator), toggle status |
| 14 | **Pengaturan** | Tab: Umum, Email, Sistem, Tentang |

---

## 📦 Instalasi

### Persyaratan
- **PHP 7.4+** (disarankan 8.x)
- **MySQL 5.7+** atau **MariaDB 10.3+**
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `openssl`
- Server web: **Apache** (XAMPP/Laragon) atau **Nginx** + PHP-FPM

### Langkah-langkah

1. **Ekstrak zip** ke folder web server Anda:
   - XAMPP: `C:\xampp\htdocs\sales_territory_app`
   - Laragon: `C:\laragon\www\sales_territory_app`
   - Linux: `/var/www/html/sales_territory_app`

2. **Import database**:
   - Buka **phpMyAdmin** (http://localhost/phpmyadmin)
   - Klik tab **Import**
   - Pilih file `database/schema.sql`
   - Klik **Go** / **Execute**
   - Database `sales_territory` akan dibuat otomatis dengan data seed

3. **Konfigurasi koneksi database**:
   - Edit file `config.php` di baris berikut:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'sales_territory');
   define('DB_USER', 'root');      // ganti jika user db Anda berbeda
   define('DB_PASS', '');           // ganti jika password db Anda berbeda
   ```

4. **Akses aplikasi**:
   - Buka browser ke `http://localhost/sales_territory_app`
   - Anda akan diarahkan ke halaman login

### 🔐 Akun Demo

| Email | Password | Peran |
|-------|----------|-------|
| `admin@telkom.co.id` | `admin123` | Administrator |
| `budi@telkom.co.id` | `admin123` | Manajer |
| `siti@telkom.co.id` | `admin123` | Operator |

---

## 📁 Struktur Folder

```
sales_territory_app/
├── config.php                 # Konfigurasi & helper functions
├── index.php                  # Entry point → login
├── login.php                  # Halaman login
├── logout.php                 # Logout handler
├── lupa_password.php          # Halaman lupa password
├── dashboard.php              # Halaman dashboard
├── data_sales.php             # Data sales
├── data_pelanggan.php         # Data pelanggan
├── peta_potensi.php           # Peta potensi wilayah
├── parameter_alokasi.php      # Parameter alokasi
├── perhitungan_haversine.php   # Perhitungan Haversine
├── optimasi_ilp.php           # Optimasi ILP
├── hasil_alokasi.php          # Hasil alokasi
├── detail_sales.php           # Detail sales
├── laporan.php                # Laporan
├── manajemen_pengguna.php     # Manajemen pengguna
├── pengaturan.php             # Pengaturan
├── .htaccess
├── README.md
├── database/
│   └── schema.sql             # Schema + seed data
├── includes/
│   ├── header.php             # Shared layout (sidebar + header)
│   └── footer.php             # Shared footer
├── ajax/
│   ├── sales_save.php
│   ├── sales_delete.php
│   ├── pelanggan_save.php
│   └── pelanggan_delete.php
└── assets/
    ├── css/
    │   └── style.css          # Stylesheet lengkap
    ├── js/
    │   ├── app.js             # JS umum (modal, toast, ajax)
    │   ├── dashboard.js
    │   ├── data_sales.js
    │   ├── data_pelanggan.js
    │   ├── peta_potensi.js
    │   ├── hasil_alokasi.js
    │   ├── detail_sales.js
    │   └── laporan.js
    └── img/                   # (kosong, untuk upload)
```

---

## 🧮 Algoritma Optimasi

### 1. Formula Haversine
Menghitung jarak spherical antara dua koordinat:
```
d = 2R · arcsin(√(sin²(Δlat/2) + cos(lat₁)·cos(lat₂)·sin²(Δlon/2)))
```
dimana R = 6371 km (jari-jari bumi).

### 2. Model ILP
**Fungsi objektif (maksimisasi skor):**
```
max ΣΣ x_ij · (w₁·(1 - d_ij/R) + w₂·p_i/100)
```

**Kendala:**
- Kapasitas: `Σ_i x_ij ≤ K_j` (pelanggan per sales ≤ kapasitas)
- Unik: `Σ_j x_ij = 1` (1 pelanggan = 1 sales)
- Biner: `x_ij ∈ {0, 1}`

### 3. Implementasi
Algoritma greedy dengan weighted score (karena server PHP murni tidak bisa jalankan solver ILP seperti PuLP/Gurobi):
1. Hitung jarak Haversine untuk semua pasangan pelanggan-sales
2. Hitung skor kecocokan: `skor = w₁·(1 - jarak/radius) + w₂·(potensi/100)`
3. Urutkan pasangan berdasarkan skor (desc)
4. Assign secara greedy dengan memperhatikan kapasitas sales
5. Skip pasangan yang jaraknya > radius maksimum

---

## 🎨 Design System

- **Primary color:** `#E31E24` (Merah Telkom)
- **Background:** `#F3F4F6` (soft gray)
- **Sidebar:** White with red active state
- **Typography:** System sans-serif (Segoe UI / Roboto / Inter)
- **Border radius:** 12px (cards), 6px (inputs)
- **Map provider:** OpenStreetMap (gratis, tanpa API key)

---

## 🛡️ Sistem Peran Pengguna

| Fitur | Administrator | Manajer | Operator |
|-------|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ |
| Data Sales (CRUD) | ✅ | ✅ | ✅ |
| Data Pelanggan (CRUD) | ✅ | ✅ | ✅ |
| Peta | ✅ | ✅ | ✅ |
| Parameter Alokasi | ✅ | ✅ | ❌ |
| Perhitungan Haversine | ✅ | ✅ | ❌ |
| Optimasi ILP | ✅ | ✅ | ❌ |
| Hasil Alokasi | ✅ | ✅ | ❌ |
| Laporan | ✅ | ✅ | ❌ |
| Manajemen Pengguna | ✅ | ❌ | ❌ |
| Pengaturan | ✅ | ❌ | ❌ |

---

## 📝 Catatan

- Password default di-hash menggunakan `password_hash()` (bcrypt) — tidak disimpan plain text.
- Aplikasi ini cocok untuk demo, prototype, atau deployment internal kecil.
- Untuk produksi: tambahkan CSRF token, rate limiting, HTTPS, dan logging.
- Data seed mencakup 8 sales dan 44 pelanggan di wilayah Madiun (Kota, Barat, Timur, Selatan, Utara, Kartoharjo, Taman, Mangunharjo).

---

## 📞 Support

Jika menemui masalah:
1. **Database tidak terhubung**: Pastikan MySQL berjalan dan kredensial di `config.php` benar.
2. **Map tidak muncul**: Pastikan ada koneksi internet (Leaflet & tile dari OpenStreetMap).
3. **Charts tidak muncul**: Pastikan Chart.js ter-load (perlu internet).
4. **Optimasi gagal**: Pastikan ada minimal 1 sales aktif dan 1 pelanggan di database.

---

© 2026 Telkom Madiun. Sistem Alokasi Sales Berdasarkan Potensi Wilayah.

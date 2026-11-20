# KreatifGallery

Platform galeri karya dan portofolio digital. Kreator mengunggah karya (gambar/video), admin memoderasi sebelum tayang, pengunjung menjelajah, menyukai, berkomentar, dan melaporkan.

**Stack:** Laravel 12 (PHP 8.2+), Blade, Eloquent, MySQL, Tailwind CSS (CDN), Lucide, SweetAlert2, Chart.js, font Plus Jakarta Sans.

## Peran

| Peran | Kemampuan |
|---|---|
| **Pengunjung** (tanpa login) | Menjelajah, mencari, memfilter, melihat detail karya dan profil kreator |
| **Pengunjung/pengguna login** | Menyukai, berkomentar, menghapus komentar sendiri, melaporkan karya/komentar, melihat notifikasi |
| **Kreator** | Semua di atas + unggah karya (1–10 file), revisi, hapus karya sendiri, portofolio, profil, dashboard |
| **Admin** | Moderasi (setujui / tolak dengan catatan wajib), turunkan karya, CRUD kategori, aktif/nonaktifkan pengguna, tangani laporan, analitik |

Admin hanya dibuat lewat seeder; registrasi publik hanya untuk kreator dan pengunjung.

## Instalasi

Prasyarat: PHP 8.2+ (ekstensi `gd`, `pdo_mysql`, `mbstring`, `fileinfo`), Composer, MySQL 8 / MariaDB 10.6+.

```bash
composer install
cp .env.example .env
php artisan key:generate

# Buat database kosong, lalu sesuaikan DB_* di .env
mysql -u root -e "CREATE DATABASE kreatifgallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate --seed
php artisan storage:link
```
## Pengujian

```bash
php artisan test
```

## Struktur singkat

```
app/Http/Controllers/        Public, Auth, Interaction, Notification, Account, Kreator/*, Admin/*
app/Http/Requests/           Validasi (Form Request)
app/Http/Middleware/         role, active
app/Policies/                KaryaPolicy, CommentPolicy, PortofolioPolicy
app/Services/                MediaUploader, KaryaService, ModerationService, NotificationService
config/kreatifgallery.php    Batas dan aturan unggah (satu sumber kebenaran)
database/migrations|seeders  Skema dan data awal
resources/views/             Layout, komponen, halaman publik/kreator/admin
public/js/                   app.js (UI bersama), uploader.js (drag-drop, progres)
tests/Feature/               Pengujian
```

## Keamanan

CSRF di semua form, autorisasi lewat middleware dan policy, validasi MIME dari isi file, output Blade ter-escape, nama berkas acak (UUID), rate limit unggah/komentar/laporan/login, akun nonaktif diblokir di login dan di setiap request.
=======
# 🎨 KreatifGallery

**Platform Galeri Karya dan Portofolio Digital Berbasis Web**

KreatifGallery adalah platform berbasis web yang dirancang sebagai ruang digital bagi para kreator untuk menampilkan, mempublikasikan, dan mengelola karya kreatif mereka dalam sebuah galeri online. Platform ini juga memungkinkan pengunjung menjelajahi berbagai karya, mengenal kreator, serta berinteraksi melalui fitur suka dan komentar.

Dibangun menggunakan **Laravel 12**, KreatifGallery menghadirkan sistem pengelolaan karya dengan moderasi admin untuk menjaga kualitas dan kelayakan konten yang dipublikasikan.

## ✨ Fitur Utama

- **Galeri Karya Digital** – Menampilkan berbagai karya kreatif dalam satu platform.
- **Eksplorasi Karya** – Menjelajahi, mencari, dan memfilter karya berdasarkan kategori.
- **Manajemen Portofolio** – Kreator dapat membangun dan mengelola portofolio digital.
- **Upload Multi-Media** – Mendukung unggahan gambar dan video.
- **Interaksi Pengguna** – Fitur like, komentar, dan pelaporan konten.
- **Sistem Autentikasi** – Registrasi dan login dengan hak akses berdasarkan peran.
- **Moderasi Admin** – Persetujuan, penolakan, dan pengelolaan publikasi karya.
- **Dashboard Kreator** – Mengelola karya dan memantau aktivitas.
- **Dashboard Admin** – Mengelola pengguna, kategori, laporan, moderasi, dan analitik.
- **Notifikasi** – Memberikan informasi terkait aktivitas dan status karya.
- **Manajemen Profil** – Mengelola informasi dan profil pengguna.

## 👥 Hak Akses Pengguna

| Role | Hak Akses |
|---|---|
| Pengunjung | Melihat galeri, mencari karya, dan melihat profil kreator |
| Pengguna | Memberikan like, komentar, dan laporan |
| Kreator | Mengunggah karya, mengelola portofolio, dan mengatur profil |
| Admin | Mengelola pengguna, kategori, moderasi, laporan, dan analitik |

## 🛠️ Teknologi yang Digunakan

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** HTML, CSS, JavaScript, Blade
- **Database:** MySQL
- **UI Framework:** Tailwind CSS
- **Icons:** Lucide
- **Alert & Dialog:** SweetAlert2
- **Data Visualization:** Chart.js
- **Typography:** Plus Jakarta Sans

## 🎯 Tujuan Pengembangan

KreatifGallery dikembangkan untuk menyediakan wadah digital yang memudahkan kreator dalam membangun identitas kreatif, memperkenalkan karya kepada publik, serta membangun interaksi antara kreator dan pengunjung melalui sebuah platform galeri dan portofolio digital.

---

**KreatifGallery – Showcase Your Creativity, Inspire the World.**

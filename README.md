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

### Batas upload PHP (penting)

Default PHP (`upload_max_filesize=2M`, `post_max_size=8M`) lebih kecil dari aturan aplikasi (20 MB per file, 10 file). Naikkan di `php.ini` (atau pool PHP-FPM):

```ini
upload_max_filesize = 20M
post_max_size = 210M
max_file_uploads = 20
```

Untuk server pengembangan bawaan PHP:

```bash
php -d upload_max_filesize=25M -d post_max_size=210M -S 127.0.0.1:8000 -t public
```

`php artisan serve` tidak meneruskan opsi `-d`, jadi atur lewat `php.ini` bila memakainya. Jika batas terlalu kecil, unggahan ditolak PHP sebelum sampai ke aplikasi. Pada server produksi, sesuaikan juga `client_max_body_size` (Nginx).

### Menjalankan

```bash
php -d upload_max_filesize=25M -d post_max_size=210M -S 127.0.0.1:8000 -t public
```

Buka `http://127.0.0.1:8000`. URL media memakai jalur relatif `/storage/...`, sehingga tidak bergantung pada `APP_URL`.

## Akun pengembangan (seeder)

> **PERINGATAN: akun dan kata sandi di bawah hanya untuk pengembangan lokal. Jangan dipakai di produksi.**
> Ganti lewat `SEED_ADMIN_PASSWORD` / `SEED_USER_PASSWORD` di `.env` sebelum menjalankan seeder, atau ubah kata sandi lewat aplikasi (Settings) segera setelah instalasi.

| Peran | Email | Kata sandi bawaan |
|---|---|---|
| Admin | `admin@kreatifgallery.test` | `admin12345` |
| Kreator | `kreator@kreatifgallery.test` | `password123` |
| Pengunjung | `pengunjung@kreatifgallery.test` | `password123` |

Saat `APP_ENV=production`, seeder hanya membuat akun dan kategori dasar; data demo (karya, suka, komentar) tidak dibuat. Seeder bersifat idempoten.

## Pengujian

```bash
php artisan test
```

Tes memakai SQLite in-memory dan disk `public` palsu (`Storage::fake`), sehingga tidak menyentuh database maupun berkas asli.

### Pemetaan 20 skenario wajib

| # | Skenario | Tes |
|---|---|---|
| 1 | Registrasi pengguna | `AuthTest::test_registration_as_kreator_and_pengunjung`, `test_cannot_register_as_admin_or_with_duplicate_email` |
| 2 | Login | `AuthTest::test_login_redirects_by_role_and_rejects_bad_password` |
| 3 | Akses berbasis peran | `AuthTest::test_role_based_access`, `AdminTest::test_admin_area_is_protected`, `SiteIntegrityTest::test_admin_only_and_creator_only_routes_reject_wrong_roles` |
| 4 | Kreator unggah satu karya | `KaryaUploadTest::test_creator_uploads_one_artwork_as_pending`, `test_thumbnail_is_webp_720_wide_for_images_and_absent_for_video` |
| 5 | Unggah banyak media | `KaryaUploadTest::test_creator_uploads_ten_files_but_not_eleven` |
| 6 | Penolakan file tidak valid | `KaryaUploadTest::test_invalid_input_is_rejected_server_side` |
| 7 | Validasi ukuran file | `KaryaUploadTest::test_exactly_20mb_and_all_allowed_formats_are_accepted`, `test_invalid_input_is_rejected_server_side` |
| 8 | Rate limiting | `KaryaUploadTest::test_upload_is_rate_limited_to_six_per_minute`, `AuthTest::test_upload_route_is_rate_limited_to_6_per_minute` |
| 9 | Moderasi tertunda | `KaryaUploadTest::test_creator_uploads_one_artwork_as_pending`, `PublicPagesTest::test_pending_and_rejected_works_are_not_public` |
| 10 | Persetujuan admin | `AdminTest::test_approve_publishes_records_admin_and_notifies` |
| 11 | Visibilitas publik karya | `PublicPagesTest::test_pending_and_rejected_works_are_not_public`, `test_owner_and_admin_can_preview_unpublished_but_others_cannot` |
| 12 | Penolakan dengan catatan | `AdminTest::test_reject_requires_note_and_does_not_change_status_without_it`, `test_reject_saves_note_admin_and_notifies_creator` |
| 13 | Revisi kreator | `KaryaUploadTest::test_rejected_artwork_revision_returns_to_pending_and_notifies`, `test_text_only_edit_of_published_work_keeps_it_public` |
| 14 | Pengiriman ulang ke moderasi | `AdminTest::test_full_cycle_reject_revise_resubmit_approve`, `KaryaUploadTest::test_changing_media_of_published_work_returns_it_to_moderation_and_hides_it` |
| 15 | Suka | `InteractionTest::test_like_toggles_and_is_unique_per_user_and_work`, `test_database_rejects_duplicate_like_rows` |
| 16 | Komentar | `InteractionTest::test_comment_is_saved_shown_and_notifies_owner`, `test_comment_validation_and_html_is_escaped`, `test_only_author_or_admin_can_delete_comment` |
| 17 | Pengiriman laporan | `InteractionTest::test_report_work_is_stored_as_new`, `test_report_comment_saves_snapshot_and_survives_deletion` |
| 18 | Penanganan laporan admin | `AdminTest::test_dismiss_report_leaves_content_untouched`, `test_deleting_reported_comment_keeps_report_as_resolved`, `test_unpublish_from_report_closes_all_open_reports_and_notifies` |
| 19 | Pembuatan notifikasi | `KaryaUploadTest::test_creator_is_notified_on_submit`, `InteractionTest::test_notification_lifecycle_read_and_isolation`, `AdminTest::test_approve_publishes_records_admin_and_notifies` |
| 20 | Penonaktifan akun | `AdminTest::test_deactivate_and_reactivate_account`, `AuthTest::test_inactive_account_cannot_login`, `AuthTest::test_deactivated_while_logged_in_gets_403`, `PublicPagesTest::test_deactivated_creator_works_and_profile_hidden` |

Selain itu `SiteIntegrityTest` menelusuri semua tautan dan form yang dirender untuk tiap peran (memastikan tidak ada route rusak atau form tanpa route), dan memeriksa konsistensi data seed (status, `published_at`, `admin_id`, berkas media, suka unik).

## Aturan bisnis utama

- **Unggah:** 1–10 file per karya, maks. 20 MB per file, format `jpg/png/webp/gif/mp4/webm` (divalidasi dari isi file, bukan hanya ekstensi), judul maks. 150 karakter, maks. 6 unggahan per menit per pengguna (`throttle:unggah`; validasi gagal juga dihitung).
- **Thumbnail:** WebP lebar 720 px untuk gambar (gambar yang lebih kecil tidak diperbesar); video tanpa thumbnail.
- **Konsistensi:** pembuatan/ubah/hapus karya dalam transaksi DB; berkas yang sempat tertulis dibersihkan jika gagal; berkas lama dihapus setelah commit, sehingga tidak ada berkas yatim.
- **Moderasi:** karya baru `menunggu` dan tidak publik. Admin menyetujui (mencatat `admin_id` dan `published_at`) atau menolak dengan catatan wajib (5–1000 karakter). Revisi karya ditolak kembali ke `menunggu`. Mengubah media karya yang sudah tayang mengembalikannya ke `menunggu`; mengubah teks saja tidak.
- **Tayang publik** hanya jika status `disetujui` **dan** pemilik masih aktif.
- **Notifikasi:** disimpan di database (tabel `notifications`), tanpa email/push.
- **Suka:** unik per pengguna dan karya (constraint database). **Laporan:** satu laporan terbuka per pelapor per target; tidak bisa melaporkan konten sendiri.

## Keputusan desain dan batasan yang perlu diketahui

- **Menurunkan karya** memakai status `ditolak` dengan catatan berawalan "Diturunkan oleh admin: …", karena ERD hanya punya tiga status.
- **Riwayat moderasi** hanya menyimpan catatan terakhir (sesuai ERD, satu kolom `catatan_moderasi`).
- **Laporan komentar** menyimpan `snapshot` isi komentar dan memakai `nullOnDelete`, sehingga laporan tetap tercatat (berstatus selesai) walau komentar dihapus.
- **Slug kategori** tetap saat nama diganti, agar tautan lama tidak rusak.
- **Library front-end** (Tailwind, Lucide, SweetAlert2, Chart.js, font) dimuat dari CDN; instalasi tidak memerlukan Node/npm, tetapi halaman butuh akses internet ke CDN tersebut.
- **Status verifikasi:** seluruh tes berjalan di SQLite. Aplikasi dikonfigurasi untuk MySQL (default di `.env.example`), tetapi **belum dijalankan terhadap MySQL sungguhan** karena tidak tersedia di lingkungan pengembangan. Tampilan diperiksa di Chromium (desktop dan mobile) dengan library CDN yang dipasang lokal; pemuatan dari CDN di internet nyata belum diuji.

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
>>>>>>> a6a9be039ff5d4f1f4bf73b01cb6a838e72877ab

# Foto untuk data demo

## Foto karya otomatis dari internet
`KontenTambahanSeeder` mengunduh foto karya dari layanan foto online, jadi tidak perlu menyiapkan apa pun:
- Foto dicari per karya sesuai kata kunci judulnya (mis. "Kopi Sanger Pagi" -> foto kopi): isi
  `PIXABAY_KEY=...` di `.env` (kunci gratis di pixabay.com/api/docs).
- Tanpa kunci (atau kunci ditolak): Picsum, foto acak tanpa kunci API.
- Matikan unduhan: `SEED_FOTO_ONLINE=false`. Jika offline atau gagal, otomatis memakai gambar buatan.
- Akun rekaan (bukan tiga nama nyata) mendapat foto potret dari randomuser.me.

Unggah manual lewat dashboard kreator tetap berfungsi seperti biasa.

## Foto asli milik kreator tertentu

Letakkan foto asli di sini, lalu jalankan `php artisan db:seed --class=KontenTambahanSeeder`.

## Foto profil
Folder `kreator/`. Nama berkas = slug nama kreator (huruf kecil, spasi jadi tanda hubung):

| Kreator           | Nama berkas (jpg / jpeg / png / webp)  |
|-------------------|-----------------------------------------|
| Syahrial Ramadhan | `kreator/syahrial-ramadhan.jpg`         |
| Subhan            | `kreator/subhan.jpg`                    |
| M. Muklis         | `kreator/m-muklis.jpg`                  |

Kalau berkas tidak ada, akun memakai avatar inisial seperti biasa.

## Foto karya (opsional)
Folder `karya/<slug-kreator>/`. Setiap gambar menjadi satu karya berkategori Fotografi,
judulnya diambil dari nama berkas (`senja-di-lampuuk.jpg` -> "Senja Di Lampuuk").
Kategori dan deskripsi bisa diubah kreator lewat dashboard setelahnya.

Ukuran maks 20 MB per berkas (sesuai batas unggah aplikasi).

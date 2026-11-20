<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * PERINGATAN: akun di bawah hanya untuk pengembangan. Ganti kata sandi sebelum produksi
     * (atur SEED_ADMIN_PASSWORD / SEED_USER_PASSWORD di .env, atau ubah lewat aplikasi).
     */
    public function run(): void
    {
        $adminPass = env('SEED_ADMIN_PASSWORD', 'admin12345');
        $userPass = env('SEED_USER_PASSWORD', 'password123');

        // Admin hanya dibuat lewat seeder (tidak ada registrasi admin) - lihat Use Case.
        $admin = User::updateOrCreate(['email' => 'admin@kreatifgallery.test'], [
            'name' => 'Admin KreatifGallery', 'password' => $adminPass,
            'role' => User::ROLE_ADMIN, 'is_active' => true,
            'bio' => 'Pengelola platform KreatifGallery.',
        ]);

        User::updateOrCreate(['email' => 'kreator@kreatifgallery.test'], [
            'name' => 'Dewi Kreator', 'password' => $userPass,
            'role' => User::ROLE_KREATOR, 'is_active' => true,
            'bio' => 'Ilustrator dan fotografer dari Aceh. Suka bercerita lewat warna.',
        ]);

        User::updateOrCreate(['email' => 'pengunjung@kreatifgallery.test'], [
            'name' => 'Budi Pengunjung', 'password' => $userPass,
            'role' => User::ROLE_PENGUNJUNG, 'is_active' => true,
        ]);

        $kategori = [
            'Ilustrasi' => 'Gambar digital maupun tradisional.',
            'Fotografi' => 'Karya foto dari berbagai genre.',
            'Desain Grafis' => 'Poster, logo, identitas visual.',
            'Animasi & Video' => 'Karya bergerak dan film pendek.',
            'Seni Rupa' => 'Lukisan, patung, dan karya rupa lainnya.',
            'Kriya' => 'Kerajinan tangan dan karya material.',
        ];

        foreach ($kategori as $nama => $deskripsi) {
            Kategori::updateOrCreate(['slug' => Str::slug($nama)], [
                'nama_kategori' => $nama, 'deskripsi' => $deskripsi, 'admin_id' => $admin->id,
            ]);
        }

        // Data demo bergambar hanya untuk pengembangan (tidak pernah di produksi).
        if (! app()->environment('production')) {
            $this->call(DemoKaryaSeeder::class);

            // Konten tambahan (kreator, karya, foto asli) dilewati saat tes agar penyemaian tetap cepat.
            if (! app()->runningUnitTests()) {
                $this->call(KontenTambahanSeeder::class);
                $this->call(FotografiTambahanSeeder::class);
            }
        }
    }
}

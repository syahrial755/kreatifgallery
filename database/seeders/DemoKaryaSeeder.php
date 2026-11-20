<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Karya;
use App\Models\Kategori;
use App\Models\Like;
use App\Models\Portofolio;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\MediaUploader;
use Database\Seeders\Concerns\MengambilFoto;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Data demo untuk pengembangan: karya bergambar melewati MediaUploader yang sama dengan jalur unggah asli,
 * sehingga thumbnail WebP ikut terbentuk. Foto diunduh sesuai judul karya (lihat Concerns\MengambilFoto);
 * bila offline/dimatikan dan saat tes, dipakai gambar gradien buatan GD. JANGAN dijalankan di produksi.
 */
class DemoKaryaSeeder extends Seeder
{
    use MengambilFoto;

    public function run(MediaUploader $uploader): void
    {
        if (Karya::exists()) {
            return; // idempoten: jangan menggandakan data demo
        }

        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $dewi = User::where('email', 'kreator@kreatifgallery.test')->firstOrFail();
        $pengunjung = User::where('email', 'pengunjung@kreatifgallery.test')->firstOrFail();
        $pass = env('SEED_USER_PASSWORD', 'password123');

        $raka = User::updateOrCreate(['email' => 'raka@kreatifgallery.test'], [
            'name' => 'Raka Mahendra', 'password' => $pass, 'role' => User::ROLE_KREATOR,
            'bio' => 'Fotografer jalanan. Mengejar cahaya sore di sudut-sudut kota.',
        ]);
        $laras = User::updateOrCreate(['email' => 'laras@kreatifgallery.test'], [
            'name' => 'Laras Wulandari', 'password' => $pass, 'role' => User::ROLE_KREATOR,
            'bio' => 'Desainer grafis dan ilustrator. Suka palet hangat.',
        ]);

        $kat = Kategori::pluck('id', 'slug');
        $palettes = [
            [[99, 91, 219], [233, 162, 59]], [[22, 163, 106], [23, 25, 35]], [[224, 82, 82], [248, 249, 252]],
            [[139, 131, 246], [36, 38, 56]], [[233, 162, 59], [99, 91, 219]], [[23, 25, 35], [139, 131, 246]],
        ];

        // [judul, kategori, pemilik, deskripsi, kata kunci foto (Inggris)]
        $judul = [
            ['Senja di Pantai Lampuuk', 'ilustrasi', $dewi, 'Matahari tenggelam di pantai yang sepi, digambar dengan warna jingga dan ungu yang lembut.', 'sunset beach'],
            ['Kota yang Tak Pernah Tidur', 'fotografi', $raka, 'Lampu kota dan lalu lintas malam hari yang tidak pernah benar-benar sepi.', 'city night lights'],
            ['Poster Festival Kopi', 'desain-grafis', $laras, 'Poster festival kopi dengan tipografi besar dan palet cokelat hangat.', 'coffee festival poster'],
            ['Hutan Hujan Digital', 'ilustrasi', $dewi, 'Lukisan digital hutan hujan tropis yang lebat dan lembap.', 'rainforest'],
            ['Bayangan di Gang Sempit', 'fotografi', $raka, 'Bayangan panjang jatuh di gang sempit pada sore hari.', 'narrow alley shadow'],
            ['Identitas Visual Kedai Teh', 'desain-grafis', $laras, 'Logo, kemasan, dan cangkir bermerek untuk sebuah kedai teh kecil.', 'tea shop branding'],
            ['Gelombang Warna', 'seni-rupa', $dewi, 'Eksplorasi gelombang warna pada kanvas, bergerak dari biru ke jingga.', 'abstract colorful waves painting'],
            ['Pasar Pagi', 'fotografi', $raka, 'Pedagang dan pembeli di pasar pagi saat cahaya pertama masuk.', 'morning market'],
            ['Tipografi Aceh Modern', 'desain-grafis', $laras, 'Eksperimen huruf modern bernuansa ornamen Aceh.', 'typography poster'],
            ['Potret Nelayan', 'fotografi', $raka, 'Potret nelayan dengan wajah yang terbakar matahari dan jaring di bahu.', 'fisherman'],
            ['Mimpi Seekor Paus', 'ilustrasi', $dewi, 'Seekor paus raksasa berenang di langit malam penuh bintang.', 'whale'],
            ['Anyaman Rotan', 'kriya', $laras, 'Keranjang rotan dianyam rapat dengan pola sederhana.', 'rattan weaving basket'],
            ['Fajar di Atas Kabut', 'fotografi', $raka, 'Matahari terbit di atas lautan kabut dari puncak bukit.', 'sunrise mist mountain'],
            ['Kucing Astronot', 'ilustrasi', $dewi, 'Kucing berhelm astronot melayang di ruang angkasa.', 'cat astronaut'],
        ];

        $likers = [$pengunjung, $dewi, $raka, $laras];
        $i = 0;
        foreach ($judul as [$title, $slug, $owner, $deskripsi, $query]) {
            $karya = $this->make($uploader, $owner, $slug, $kat[$slug], $title, $deskripsi, $query, Karya::DISETUJUI, $palettes[$i % 6], $i % 3 === 0 ? 3 : 1, $admin);
            $karya->update(['published_at' => now()->subDays(count($judul) - $i)]);
            foreach (array_slice($likers, 0, ($i * 3) % 5) as $liker) {
                if ($liker->id !== $owner->id) {
                    Like::firstOrCreate(['user_id' => $liker->id, 'karya_id' => $karya->id]);
                }
            }
            $i++;
        }

        $this->make($uploader, $dewi, 'ilustrasi', $kat['ilustrasi'], 'Sketsa Naga Laut (draf)', 'Draf sketsa naga laut, masih menunggu peninjauan admin.', 'sea dragon', Karya::MENUNGGU, $palettes[1], 1);
        $this->make($uploader, $raka, 'fotografi', $kat['fotografi'], 'Hujan di Jembatan', 'Gerimis di atas jembatan kota, masih menunggu peninjauan admin.', 'rain bridge', Karya::MENUNGGU, $palettes[2], 2);
        $ditolak = $this->make($uploader, $dewi, 'seni-rupa', $kat['seni-rupa'], 'Studi Warna Abstrak', 'Studi warna abstrak.', 'abstract color', Karya::DITOLAK, $palettes[3], 1, $admin);
        $ditolak->update(['catatan_moderasi' => 'Resolusi terlalu rendah dan terlihat buram. Unggah ulang versi yang lebih tajam.']);

        $first = Karya::where('user_id', $dewi->id)->where('status', Karya::DISETUJUI)->first();
        Comment::create(['user_id' => $pengunjung->id, 'karya_id' => $first->id, 'isi_komentar' => 'Warnanya hangat sekali. Suka banget komposisinya!']);
        Comment::create(['user_id' => $raka->id, 'karya_id' => $first->id, 'isi_komentar' => 'Cahayanya dapat. Boleh tahu pakai brush apa?']);

        $porto = Portofolio::create(['user_id' => $dewi->id, 'judul' => 'Ilustrasi Pilihan 2026', 'deskripsi' => 'Kumpulan ilustrasi terbaik tahun ini.', 'is_public' => true]);
        $porto->karyas()->sync(Karya::where('user_id', $dewi->id)->where('status', Karya::DISETUJUI)->pluck('id'));

        $dewi->notify(new AppNotification('approved', "Karya \"{$first->judul}\" disetujui dan kini tampil di galeri.", route('karya.show', $first)));
        $dewi->notify(new AppNotification('rejected', "Karya \"{$ditolak->judul}\" ditolak. Catatan admin: {$ditolak->catatan_moderasi}", route('kreator.karya.edit', $ditolak)));
    }

    private function make(MediaUploader $uploader, User $owner, string $slugKat, int $kategoriId, string $title, string $deskripsi, ?string $query, string $status, array $palette, int $files, ?User $admin = null): Karya
    {
        $karya = Karya::create([
            'user_id' => $owner->id, 'kategori_id' => $kategoriId, 'judul' => $title,
            'deskripsi' => $deskripsi,
            'status' => $status, 'admin_id' => $status === Karya::MENUNGGU ? null : $admin?->id,
            'published_at' => $status === Karya::DISETUJUI ? now() : null,
        ]);

        $written = [];
        $tmp = [];
        $items = [];
        for ($n = 0; $n < $files; $n++) {
            $h = [900, 1200, 750][($karya->id + $n) % 3];
            // Foto online sesuai judul; bila gagal/dimatikan (atau saat tes) pakai gradien buatan.
            $path = $this->ambilFoto($slugKat, $query, $karya->id * 7 + $n, 1200, $h) ?? $this->gradient($palette, 1200, $h, $n);
            $tmp[] = $path;
            $items[] = new UploadedFile($path, "demo-{$karya->id}-{$n}.jpg", 'image/jpeg', null, true);
        }
        $uploader->store($karya, $items, 0, $written);
        foreach ($tmp as $t) {
            @unlink($t);
        }

        return $karya;
    }

    /** Gambar gradien diagonal sederhana dengan GD. */
    private function gradient(array $palette, int $w, int $h, int $shift): string
    {
        [$a, $b] = $palette;
        if ($shift % 2) {
            [$a, $b] = [$b, $a];
        }
        $img = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $row = imagecolorallocate($img, (int) ($a[0] + ($b[0] - $a[0]) * $t), (int) ($a[1] + ($b[1] - $a[1]) * $t), (int) ($a[2] + ($b[2] - $a[2]) * $t));
            imageline($img, 0, $y, $w, $y, $row);
        }
        $circle = imagecolorallocatealpha($img, 255, 255, 255, 95);
        imagefilledellipse($img, (int) ($w * 0.7), (int) ($h * 0.35), (int) ($w * 0.45), (int) ($w * 0.45), $circle);
        imagefilledellipse($img, (int) ($w * 0.25), (int) ($h * 0.75), (int) ($w * 0.3), (int) ($w * 0.3), $circle);

        $file = tempnam(sys_get_temp_dir(), 'kgdemo');
        imagepng($img, $file);
        imagedestroy($img);

        return $file;
    }
}

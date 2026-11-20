<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Karya;
use App\Models\Kategori;
use App\Models\Like;
use App\Models\Portofolio;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\MediaUploader;
use Database\Seeders\Concerns\MengambilFoto;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Konten demo tambahan: kreator baru (termasuk Syahrial Ramadhan, Subhan, M. Muklis), lebih banyak karya,
 * suka, komentar, portofolio, antrean moderasi, dan laporan. Aman dijalankan di atas data yang sudah ada:
 *
 *   php artisan db:seed --class=KontenTambahanSeeder
 *
 * Foto karya diunduh per karya sesuai kata kunci judulnya (lihat Concerns\MengambilFoto; butuh PIXABAY_KEY di
 * .env, tanpa kunci memakai Picsum). Bila offline/gagal, otomatis memakai gambar buatan GD. Foto asli
 * (profil & karya) dari database/seeders/assets tetap diutamakan - lihat README di sana.
 * JANGAN dijalankan di produksi.
 */
class KontenTambahanSeeder extends Seeder
{
    use MengambilFoto;

    private const PALET = [
        [[99, 91, 219], [233, 162, 59]], [[22, 163, 106], [23, 25, 35]], [[224, 82, 82], [248, 230, 200]],
        [[139, 131, 246], [36, 38, 56]], [[233, 162, 59], [99, 91, 219]], [[23, 25, 35], [139, 131, 246]],
        [[14, 116, 144], [250, 204, 21]], [[190, 24, 93], [254, 215, 170]], [[30, 64, 175], [125, 211, 252]],
        [[67, 20, 7], [251, 191, 36]],
    ];

    private const GAYA = [
        'fotografi' => 'gunung', 'ilustrasi' => 'lingkaran', 'desain-grafis' => 'garis',
        'seni-rupa' => 'gelombang', 'kriya' => 'titik',
    ];

    private const KOMENTAR = [
        'Komposisinya rapi sekali, enak dilihat lama-lama.',
        'Warnanya hidup! Boleh tahu palet yang dipakai?',
        'Detailnya halus banget, keren.',
        'Suka banget suasananya, bikin tenang.',
        'Proses pengerjaannya berapa lama, Kak?',
        'Mantap, ditunggu karya berikutnya!',
        'Pencahayaannya dapat sekali.',
        'Idenya segar dan beda dari yang lain.',
        'Cocok banget dijadikan poster di dinding.',
        'Salut sama ketelitiannya. Sukses terus!',
        'Kontrasnya bagus, fokusnya jelas.',
        'Bikin kangen kampung halaman. Terima kasih sudah berbagi!',
        'Ini favorit saya di galeri minggu ini.',
        'Boleh minta tips pengambilan sudutnya?',
        'Tekstur dan warnanya enak banget dipandang.',
        'Keren, bisa dipesan nggak, Kak?',
    ];

    private int $urut = 0;

    public function run(MediaUploader $uploader): void
    {
        if (User::where('email', 'syahrial@kreatifgallery.test')->exists()) {
            return; // idempoten: jangan menggandakan konten tambahan
        }

        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $kat = Kategori::pluck('id', 'slug');
        $pass = env('SEED_USER_PASSWORD', 'password123');
        mt_srand(2026);

        $pengunjung = [];
        $daftarPengunjung = [
            'Sari Wulan' => 'women', 'Yusuf Hakim' => 'men', 'Maya Anggraini' => 'women', 'Dimas Prakoso' => 'men',
            'Rina Safitri' => 'women', 'Fikri Maulana' => 'men', 'Nurul Hidayah' => 'women', 'Bima Ardiansyah' => 'men',
        ];
        foreach ($daftarPengunjung as $nama => $jk) {
            $u = User::updateOrCreate(['email' => Str::slug($nama).'@kreatifgallery.test'], [
                'name' => $nama, 'password' => $pass, 'role' => User::ROLE_PENGUNJUNG, 'is_active' => true,
            ]);
            $this->fotoPotretOnline($u, $jk);
            $pengunjung[] = $u;
        }

        $kreators = [];
        $disetujui = [];
        foreach ($this->kreator() as $row) {
            $owner = User::updateOrCreate(['email' => $row['email']], [
                'name' => $row['name'], 'password' => $pass, 'role' => User::ROLE_KREATOR,
                'is_active' => true, 'bio' => $row['bio'],
            ]);
            $slug = Str::slug($row['name']);
            $this->pasangFotoProfil($owner, $slug);
            if (isset($row['jk'])) {
                $this->fotoPotretOnline($owner, $row['jk']); // hanya kreator rekaan; tiga nama nyata memakai foto asli milik mereka
            }
            $kreators[] = $owner;

            $milik = [];
            foreach ($row['karya'] as $n => [$judul, $slugKat, $deskripsi, $query]) {
                $milik[] = $disetujui[] = $this->buat(
                    $uploader, $owner, $slugKat, $kat[$slugKat], $judul, $deskripsi, $query, Karya::DISETUJUI,
                    self::PALET[$this->urut++ % count(self::PALET)], $n % 4 === 1 ? 3 : 1, $admin
                );
            }

            // Foto asli karya (jika ada): satu gambar = satu karya Fotografi.
            foreach ($this->berkas('karya/'.$slug) as $foto) {
                $judul = Str::headline(pathinfo($foto, PATHINFO_FILENAME));
                $milik[] = $disetujui[] = $this->buat(
                    $uploader, $owner, 'fotografi', $kat['fotografi'], $judul, "Foto \"$judul\" oleh {$owner->name}.", null,
                    Karya::DISETUJUI, self::PALET[0], 0, $admin, [$foto]
                );
            }

            $porto = Portofolio::create([
                'user_id' => $owner->id, 'judul' => 'Pilihan Karya '.$owner->name,
                'deskripsi' => 'Kumpulan karya pilihan.', 'is_public' => true,
            ]);
            $porto->karyas()->sync(collect($milik)->pluck('id'));

            if ($row['nyata'] ?? false) {
                $pertama = $milik[0];
                $owner->notify(new AppNotification('approved', "Karya \"{$pertama->judul}\" disetujui dan kini tampil di galeri.", route('karya.show', $pertama)));
            }
        }

        // Antrean moderasi & karya ditolak, supaya halaman admin tidak kosong.
        $this->buat($uploader, $kreators[0], 'desain-grafis', $kat['desain-grafis'], 'Draf Poster Acara Kampus', 'Draf poster acara kampus, menunggu peninjauan admin.', 'event poster', Karya::MENUNGGU, self::PALET[2], 1, null);
        $this->buat($uploader, $kreators[1], 'ilustrasi', $kat['ilustrasi'], 'Sketsa Maskot Baru', 'Sketsa awal maskot, menunggu peninjauan admin.', 'cute mascot character', Karya::MENUNGGU, self::PALET[3], 2, null);
        $this->buat($uploader, $kreators[3], 'ilustrasi', $kat['ilustrasi'], 'Ilustrasi Sampul Zine', 'Ilustrasi untuk sampul zine komunitas.', 'zine cover art', Karya::MENUNGGU, self::PALET[6], 1, null);
        $this->buat($uploader, $kreators[4], 'fotografi', $kat['fotografi'], 'Seri Pelabuhan Sore', 'Seri foto pelabuhan menjelang senja.', 'harbor sunset', Karya::MENUNGGU, self::PALET[7], 3, null);
        $this->buat($uploader, $kreators[8], 'fotografi', $kat['fotografi'], 'Sarapan Pagi di Warung', 'Foto sarapan di warung pinggir jalan.', 'street breakfast', Karya::MENUNGGU, self::PALET[4], 1, null);
        $ditolak = $this->buat($uploader, $kreators[2], 'kriya', $kat['kriya'], 'Foto Produk Anyaman (blur)', 'Foto produk anyaman.', 'woven basket', Karya::DITOLAK, self::PALET[1], 1, $admin);
        $ditolak->update(['catatan_moderasi' => 'Foto kurang fokus dan memuat watermark pihak lain. Unggah ulang versi yang tajam tanpa watermark.']);

        // Suka & komentar dari seluruh pengguna non-admin (selain pemilik karya).
        $pelaku = User::whereIn('role', [User::ROLE_PENGUNJUNG, User::ROLE_KREATOR])->get();
        foreach ($disetujui as $karya) {
            $calon = $pelaku->where('id', '!=', $karya->user_id)->shuffle()->values();
            foreach ($calon->take(mt_rand(1, 12)) as $u) {
                Like::firstOrCreate(['user_id' => $u->id, 'karya_id' => $karya->id]);
            }
            if (mt_rand(0, 9) < 7) {
                foreach ($calon->take(mt_rand(1, 4)) as $u) {
                    $this->komentar($u, $karya, self::KOMENTAR[mt_rand(0, count(self::KOMENTAR) - 1)]);
                }
            }
        }

        // Dua laporan contoh untuk halaman admin (satu karya, satu komentar).
        $fiktif = collect($disetujui)->first(fn (Karya $k) => $k->user_id === $kreators[3]->id);
        Report::create([
            'user_id' => $pengunjung[1]->id, 'karya_id' => $fiktif->id,
            'alasan' => 'Gambarnya mirip karya seniman lain yang pernah saya lihat. Mohon dicek.',
        ]);
        $spam = $this->komentar($pengunjung[3], $disetujui[2], 'Jasa tambah followers murah, cek profil saya!!!');
        Report::create([
            'user_id' => $pengunjung[2]->id, 'komentar_id' => $spam->id,
            'alasan' => 'Komentar berisi promosi/spam.', 'snapshot' => $spam->isi_komentar,
        ]);
    }

    private function komentar(User $user, Karya $karya, string $isi): Comment
    {
        $waktu = $karya->published_at->copy()->addHours(mt_rand(1, 300));
        if ($waktu->isFuture()) {
            $waktu = now()->subMinutes(mt_rand(5, 600));
        }

        $c = new Comment(['user_id' => $user->id, 'karya_id' => $karya->id, 'isi_komentar' => $isi]);
        $c->created_at = $waktu;
        $c->updated_at = $waktu;
        $c->save();

        return $c;
    }

    /**
     * Nama kreator nyata di bawah ini boleh diubah. Bio sengaja netral; karya bawaan hanyalah contoh dengan
     * foto online sesuai judul sampai foto asli diletakkan di database/seeders/assets.
     *
     * @return array<int, array{name: string, email: string, bio: string, jk?: string, nyata?: bool, karya: array<int, array{0: string, 1: string, 2: string, 3: string}>}>
     */
    private function kreator(): array
    {
        return [
            ['name' => 'Syahrial Ramadhan', 'email' => 'syahrial@kreatifgallery.test', 'nyata' => true,
                'bio' => 'Kreator visual di KreatifGallery. Fotografi dan desain grafis.',
                'karya' => [
                    ['Lanskap Sawah Selepas Hujan', 'fotografi', 'Hamparan sawah dengan kabut tipis sesaat setelah hujan reda, dipotret dari pematang.', 'rice field mist'],
                    ['Warung Kopi Pukul Lima', 'fotografi', 'Suasana warung kopi di jam pulang kerja: uap kopi, obrolan, dan cahaya sore.', 'coffee shop evening'],
                    ['Dermaga Subuh', 'fotografi', 'Dermaga kecil yang masih sepi saat langit baru berubah warna.', 'pier sunrise'],
                    ['Brosur Wisata Sabang', 'desain-grafis', 'Desain brosur lipat tiga untuk promosi wisata pulau dengan palet biru laut.', 'tropical island travel brochure'],
                    ['Logo Kedai Mie Aceh', 'desain-grafis', 'Logo sederhana dan mudah dikenali untuk kedai mie, lengkap dengan variasi satu warna.', 'noodle restaurant logo'],
                    ['Cahaya di Sela Daun', 'fotografi', 'Studi cahaya matahari pagi yang menembus rimbun daun.', 'sunlight through leaves'],
                    ['Kalender Meja 2027', 'desain-grafis', 'Kalender meja dua belas halaman dengan tipografi bersih dan ruang catatan.', 'desk calendar'],
                    ['Sudut Kota Lama', 'fotografi', 'Bangunan tua dan bayangan panjang di sudut kota pada sore hari.', 'old town street'],
                    ['Embun di Ujung Daun', 'fotografi', 'Tetes embun pagi di ujung daun, difoto dari jarak dekat.', 'dew drops leaf'],
                    ['Menu Kafe Bertema Tropis', 'desain-grafis', 'Desain menu kafe dengan ilustrasi daun dan warna hijau segar.', 'cafe menu design'],
                    ['Jembatan Tua Berkabut', 'fotografi', 'Jembatan tua yang separuh tertutup kabut pagi.', 'old bridge fog'],
                    ['Undangan Pernikahan Minimalis', 'desain-grafis', 'Kartu undangan dengan bingkai tipis dan huruf serif yang elegan.', 'wedding invitation card'],
                ]],
            ['name' => 'Subhan', 'email' => 'subhan@kreatifgallery.test', 'nyata' => true,
                'bio' => 'Kreator visual di KreatifGallery. Ilustrasi dan seni rupa.',
                'karya' => [
                    ['Penjaga Hutan', 'ilustrasi', 'Ilustrasi digital makhluk penjaga hutan dengan nuansa hijau tua dan emas.', 'forest guardian fantasy'],
                    ['Ombak dan Perahu Kecil', 'ilustrasi', 'Perahu kecil di tengah ombak, digambar dengan sapuan warna tebal.', 'boat waves'],
                    ['Maskot Kampus', 'ilustrasi', 'Rancangan karakter maskot ramah dengan beberapa ekspresi dan pose.', 'cute mascot character'],
                    ['Cat Air: Pagi di Desa', 'seni-rupa', 'Lukisan cat air suasana desa saat kabut pagi belum hilang.', 'watercolor village'],
                    ['Tangan yang Bercerita', 'seni-rupa', 'Studi tangan dengan arang, menekankan gestur dan bayangan.', 'charcoal drawing hands'],
                    ['Sampul Buku Dongeng Pesisir', 'ilustrasi', 'Ilustrasi sampul untuk buku dongeng anak bertema laut.', 'fairy tale sea'],
                    ['Sketsa Arsitektur Masjid', 'seni-rupa', 'Sketsa tinta garis halus pada fasad dan kubah masjid.', 'mosque sketch'],
                    ['Kelinci Pembawa Lentera', 'ilustrasi', 'Kelinci kecil menembus malam dengan lentera, nuansa biru keunguan.', 'rabbit lantern night'],
                    ['Naga di Atas Awan', 'ilustrasi', 'Naga panjang meliuk di antara awan senja.', 'dragon clouds'],
                    ['Lukisan Minyak: Senja di Ladang', 'seni-rupa', 'Ladang luas di bawah langit jingga, dilukis dengan kuas lebar.', 'oil painting sunset field'],
                    ['Robot dan Burung Pipit', 'ilustrasi', 'Robot tua yang berteman dengan seekor burung pipit.', 'robot bird'],
                    ['Studi Wajah dengan Pensil', 'seni-rupa', 'Latihan arsir wajah dengan pensil grafit, fokus pada bayangan halus.', 'pencil portrait drawing'],
                ]],
            ['name' => 'M. Muklis', 'email' => 'muklis@kreatifgallery.test', 'nyata' => true,
                'bio' => 'Kreator visual di KreatifGallery. Kriya dan dokumentasi pengrajin.',
                'karya' => [
                    ['Tas Anyaman Pandan', 'kriya', 'Tas anyaman pandan dengan pola geometris dan tali kulit.', 'woven bag'],
                    ['Ukiran Motif Pinto Aceh', 'kriya', 'Panel kayu berukir motif pinto Aceh, dikerjakan dengan pahat tangan.', 'wood carving'],
                    ['Gelas Tanah Liat Glasir Biru', 'kriya', 'Set gelas tanah liat dibakar dengan glasir biru yang bervariasi.', 'ceramic cups blue glaze'],
                    ['Potret Pengrajin', 'fotografi', 'Potret pengrajin anyaman saat bekerja, cahaya alami dari jendela.', 'artisan working hands'],
                    ['Tenun Songket Warna Senja', 'kriya', 'Kain songket dengan benang emas dan gradasi warna senja.', 'songket weaving fabric'],
                    ['Label Kemasan Madu Hutan', 'desain-grafis', 'Desain label kemasan madu dengan ilustrasi lebah dan dedaunan.', 'honey jar label'],
                    ['Detail Anyaman', 'fotografi', 'Foto makro serat anyaman yang memperlihatkan tekstur dan pola.', 'basket weave texture'],
                    ['Lampu Gantung Rotan', 'kriya', 'Lampu gantung rotan dengan anyaman terbuka dan cahaya hangat.', 'rattan pendant lamp'],
                    ['Keranjang Bambu Pasar', 'kriya', 'Keranjang bambu kokoh untuk belanja ke pasar, dianyam rapat.', 'bamboo basket'],
                    ['Tangan Pembuat Gerabah', 'fotografi', 'Tangan pengrajin membentuk tanah liat di atas meja putar.', 'potter hands clay'],
                    ['Kemasan Kopi Gayo', 'desain-grafis', 'Rancangan kemasan kopi dengan warna bumi dan tipografi tegas.', 'coffee bag packaging'],
                    ['Papan Nama Kayu Pahat', 'kriya', 'Papan nama kayu dengan huruf dipahat dan dipernis halus.', 'carved wooden sign'],
                ]],
            ['name' => 'Nadia Putri', 'email' => 'nadia@kreatifgallery.test', 'jk' => 'women',
                'bio' => 'Ilustrator buku anak. Gemar tinta dan warna pastel.',
                'karya' => [
                    ['Kereta Kertas', 'ilustrasi', 'Kereta dari lipatan kertas yang melaju di atas peta, gaya pastel.', 'paper train'],
                    ['Taman Rahasia Nenek', 'ilustrasi', 'Taman penuh bunga dan kucing tidur, dari kenangan masa kecil.', 'secret garden flowers'],
                    ['Burung Hantu Pustakawan', 'ilustrasi', 'Burung hantu berkacamata yang menjaga rak buku tua.', 'owl books'],
                    ['Peta Pulau Imajiner', 'ilustrasi', 'Peta pulau fiktif lengkap dengan legenda dan makhluk laut.', 'fantasy island map'],
                    ['Sarapan di Bulan', 'ilustrasi', 'Dua astronaut kecil sarapan roti di permukaan bulan.', 'astronaut moon'],
                    ['Rubah Penjelajah Hujan', 'ilustrasi', 'Rubah kecil berpayung daun menyusuri hutan saat hujan.', 'fox rain umbrella'],
                    ['Kota di Dalam Teko', 'ilustrasi', 'Kota mungil dengan menara dan jembatan yang tumbuh di dalam teko teh.', 'teapot fantasy'],
                    ['Piknik Para Jamur', 'ilustrasi', 'Keluarga jamur berpiknik di bawah cahaya bulan.', 'mushrooms forest fairy'],
                ]],
            ['name' => 'Fajar Pratama', 'email' => 'fajar@kreatifgallery.test', 'jk' => 'men',
                'bio' => 'Fotografer perjalanan dan dokumenter.',
                'karya' => [
                    ['Kabut di Gayo', 'fotografi', 'Kebun kopi di dataran tinggi yang diselimuti kabut tebal.', 'coffee plantation mist'],
                    ['Perahu Nelayan Pagi', 'fotografi', 'Deretan perahu nelayan menunggu air pasang.', 'fishing boats morning'],
                    ['Jejak di Pasir Hitam', 'fotografi', 'Jejak kaki di pasir hitam pantai yang sepi.', 'footprints black sand beach'],
                    ['Pasar Malam', 'fotografi', 'Lampu-lampu warna-warni dan keramaian pasar malam.', 'night market lights'],
                    ['Lorong Kota Tua', 'fotografi', 'Lorong sempit dengan dinding berlapis cat yang mengelupas.', 'old alley peeling walls'],
                    ['Kereta Senja', 'fotografi', 'Kereta melintas di tepi sawah saat matahari tenggelam.', 'train sunset'],
                    ['Gunung dari Jendela Bus', 'fotografi', 'Siluet gunung terlihat dari jendela bus di jalan berkelok.', 'mountain road travel'],
                    ['Kapal di Cakrawala', 'fotografi', 'Kapal kargo kecil di garis cakrawala yang kemerahan.', 'ship horizon'],
                ]],
            ['name' => 'Intan Permata', 'email' => 'intan@kreatifgallery.test', 'jk' => 'women',
                'bio' => 'Pengrajin tekstil dan keramik kecil.',
                'karya' => [
                    ['Batik Tulis Pucuk Rebung', 'kriya', 'Batik tulis motif pucuk rebung dengan pewarna alami.', 'batik'],
                    ['Vas Keramik Raku', 'kriya', 'Vas keramik teknik raku dengan retakan glasir yang unik.', 'ceramic vase'],
                    ['Kalung Manik Kayu', 'kriya', 'Kalung manik kayu yang diamplas halus dan diwarnai tangan.', 'wooden bead necklace'],
                    ['Taplak Sulam Kasab', 'kriya', 'Taplak meja dengan sulaman kasab bermotif flora.', 'embroidery tablecloth'],
                    ['Mangkuk Tanah Liat', 'kriya', 'Mangkuk tanah liat buatan tangan dengan bibir sedikit tak beraturan.', 'clay bowl handmade'],
                    ['Gantungan Kunci Rajut', 'kriya', 'Gantungan kunci rajut berbentuk hewan kecil.', 'crochet keychain'],
                    ['Tas Tenun Pastel', 'kriya', 'Tas tenun dengan benang pastel dan lapisan dalam kain katun.', 'woven tote bag'],
                    ['Tikar Pandan Anyam', 'kriya', 'Tikar pandan anyaman rapat dengan garis warna di tepinya.', 'woven mat'],
                ]],
            ['name' => 'Teuku Fadhil', 'email' => 'fadhil@kreatifgallery.test', 'jk' => 'men',
                'bio' => 'Pelukis ekspresionis yang gemar warna kuat.',
                'karya' => [
                    ['Badai di Selat', 'seni-rupa', 'Lukisan badai laut dengan sapuan pisau palet.', 'stormy sea painting'],
                    ['Potret Kakek', 'seni-rupa', 'Potret kakek dengan garis wajah yang tegas dan hangat.', 'old man portrait painting'],
                    ['Komposisi Merah dan Biru', 'seni-rupa', 'Eksplorasi bentuk dan warna kontras pada kanvas besar.', 'abstract red blue painting'],
                    ['Penari dari Tanah Liat', 'seni-rupa', 'Patung tanah liat penari dengan pose yang sedang bergerak.', 'clay sculpture dancer'],
                    ['Pasar dalam Warna', 'seni-rupa', 'Keramaian pasar diterjemahkan menjadi bidang-bidang warna cerah.', 'colorful market painting'],
                    ['Ladang Kuning', 'seni-rupa', 'Hamparan ladang kuning di bawah langit biru pekat.', 'yellow field painting'],
                    ['Topeng dan Bayangan', 'seni-rupa', 'Topeng kayu dan bayangannya pada dinding putih.', 'mask shadow art'],
                    ['Mural Dinding Kota', 'seni-rupa', 'Mural besar di dinding gang yang sebelumnya kusam.', 'street mural'],
                ]],
            ['name' => 'Putri Maharani', 'email' => 'putri@kreatifgallery.test', 'jk' => 'women',
                'bio' => 'Desainer identitas merek untuk UMKM.',
                'karya' => [
                    ['Rebranding Toko Roti Kecil', 'desain-grafis', 'Identitas visual baru toko roti: logo, kemasan, dan stempel.', 'bakery branding'],
                    ['Poster Konser Akustik', 'desain-grafis', 'Poster konser dengan tipografi besar dan warna hangat.', 'concert poster'],
                    ['Kemasan Teh Herbal', 'desain-grafis', 'Rangkaian kemasan teh herbal dengan ilustrasi tanaman.', 'herbal tea packaging'],
                    ['Infografik Daur Ulang', 'desain-grafis', 'Infografik langkah memilah dan mendaur ulang sampah rumah tangga.', 'recycling infographic'],
                    ['Set Ikon Pasar Tradisional', 'desain-grafis', 'Dua puluh ikon bergaya garis untuk aplikasi pasar tradisional.', 'icon set'],
                    ['Kartu Nama Studio Foto', 'desain-grafis', 'Kartu nama dua sisi dengan cetak emas panas.', 'business card design'],
                    ['Spanduk Bazar Ramadan', 'desain-grafis', 'Spanduk bazar dengan lentera dan huruf kaligrafi modern.', 'banner bazaar'],
                    ['Desain Tote Bag Komunitas', 'desain-grafis', 'Desain tote bag komunitas dengan sablon dua warna.', 'tote bag design'],
                ]],
            ['name' => 'Cut Rahmi', 'email' => 'rahmi@kreatifgallery.test', 'jk' => 'women',
                'bio' => 'Fotografer makanan dan kuliner nusantara.',
                'karya' => [
                    ['Mie Aceh Pedas', 'fotografi', 'Semangkuk mie berkuah rempah yang masih mengepul.', 'spicy noodles'],
                    ['Kopi Sanger Pagi', 'fotografi', 'Segelas kopi susu di meja kayu dengan cahaya jendela.', 'coffee latte wooden table'],
                    ['Kue Timphan Daun Pisang', 'fotografi', 'Kue tradisional terbungkus daun pisang, siap disantap.', 'banana leaf cake'],
                    ['Rujak Buah Segar', 'fotografi', 'Potongan buah segar dengan bumbu rujak yang pekat.', 'fruit salad'],
                    ['Teh Tarik Sore', 'fotografi', 'Teh tarik berbusa di gelas kaca, latar warung sore.', 'milk tea'],
                    ['Nasi Gurih Sarapan', 'fotografi', 'Nasi gurih dengan lauk lengkap di atas piring anyaman.', 'rice breakfast'],
                    ['Martabak Manis Hangat', 'fotografi', 'Martabak manis yang baru diangkat dari wajan.', 'sweet pancake'],
                    ['Sate Matang Arang', 'fotografi', 'Sate dibakar di atas bara dengan asap tipis.', 'grilled satay'],
                ]],
            ['name' => 'Andika Saputra', 'email' => 'andika@kreatifgallery.test', 'jk' => 'men',
                'bio' => 'Concept artist. Dunia fantasi dan fiksi ilmiah.',
                'karya' => [
                    ['Kota Terapung', 'ilustrasi', 'Konsep kota yang melayang di atas lautan awan.', 'floating city fantasy'],
                    ['Ksatria Berkarat', 'ilustrasi', 'Ksatria dengan zirah tua yang berkarat di tengah reruntuhan.', 'knight armor'],
                    ['Kapal Layar Langit', 'ilustrasi', 'Kapal layar besar berlayar di antara pulau-pulau melayang.', 'sky ship'],
                    ['Gua Kristal', 'ilustrasi', 'Gua bawah tanah penuh kristal yang memantulkan cahaya biru.', 'crystal cave'],
                    ['Kedai Ramen Neon', 'ilustrasi', 'Kedai ramen kecil di gang kota bercahaya neon.', 'neon city'],
                    ['Hutan Lampu', 'ilustrasi', 'Hutan malam yang pohon-pohonnya bercahaya lembut.', 'glowing forest'],
                    ['Penyihir Petir', 'ilustrasi', 'Penyihir yang memanggil petir di puncak bukit.', 'wizard lightning'],
                    ['Planet Es', 'ilustrasi', 'Penjelajah kecil di permukaan planet es yang sunyi.', 'ice planet'],
                ]],
            ['name' => 'Nabila Azzahra', 'email' => 'nabila@kreatifgallery.test', 'jk' => 'women',
                'bio' => 'Desainer motif dan tekstil.',
                'karya' => [
                    ['Motif Flora Pastel', 'desain-grafis', 'Pola berulang bunga dalam palet pastel untuk kain.', 'floral pattern'],
                    ['Pola Geometris Islami', 'desain-grafis', 'Pola geometris simetris terinspirasi ornamen Islami.', 'islamic geometric pattern'],
                    ['Katalog Fashion Muslim', 'desain-grafis', 'Tata letak katalog busana dengan foto besar dan teks ringkas.', 'fashion catalog'],
                    ['Sampul Majalah Kampus', 'desain-grafis', 'Sampul majalah kampus edisi khusus dengan judul bertipografi besar.', 'magazine cover'],
                    ['Kerudung Cetak Digital', 'kriya', 'Kerudung dengan motif cetak digital yang jatuh lembut.', 'scarf fabric print'],
                    ['Sulaman Tangan Bunga', 'kriya', 'Bunga-bunga kecil disulam tangan pada kain linen.', 'hand embroidery flowers'],
                    ['Bros Kain Perca', 'kriya', 'Bros dari potongan kain perca bermotif ceria.', 'fabric brooch'],
                    ['Totebag Batik Modern', 'kriya', 'Totebag dengan panel batik bermotif modern.', 'batik tote bag'],
                ]],
        ];
    }

    /** @return string[] berkas gambar (jpg/jpeg/png/webp) di database/seeders/assets/$dir, terurut */
    private function berkas(string $dir): array
    {
        $path = database_path('seeders/assets/'.$dir);
        if (! is_dir($path)) {
            return [];
        }

        $out = [];
        foreach (scandir($path) ?: [] as $f) {
            if (preg_match('/\.(jpe?g|png|webp)$/i', $f)) {
                $out[] = $path.'/'.$f;
            }
        }
        sort($out);

        return $out;
    }

    private function pasangFotoProfil(User $user, string $slug): void
    {
        $dir = database_path('seeders/assets/kreator');
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $f) {
            if (preg_match('/^'.preg_quote($slug, '/').'\.(jpe?g|png|webp)$/i', $f, $m)) {
                $path = Storage::disk('public')->putFileAs('profil', new File($dir.'/'.$f), Str::uuid().'.'.strtolower($m[1]));
                $user->update(['foto_profil' => $path]);

                return;
            }
        }
    }

    /** Potret rekaan (randomuser.me, 0-99) untuk akun rekaan; dilewati bila offline. */
    private function fotoPotretOnline(User $user, string $jk): void
    {
        $file = $this->unduhUrl('https://randomuser.me/api/portraits/'.$jk.'/'.(($user->id * 7) % 100).'.jpg');
        if (! $file) {
            return;
        }
        $path = Storage::disk('public')->putFileAs('profil', new File($file), Str::uuid().'.jpg');
        @unlink($file);
        $user->update(['foto_profil' => $path]);
    }

    /**
     * @param  string[]  $foto  gambar asli milik kreator; dipakai lebih dulu, lalu $jumlah gambar tambahan
     *                          (foto online sesuai $query, atau gambar buatan bila gagal)
     */
    private function buat(MediaUploader $uploader, User $owner, string $slugKat, int $kategoriId, string $judul, string $deskripsi, ?string $query, string $status, array $palet, int $jumlah, ?User $admin, array $foto = []): Karya
    {
        $karya = Karya::create([
            'user_id' => $owner->id, 'kategori_id' => $kategoriId, 'judul' => $judul, 'deskripsi' => $deskripsi,
            'status' => $status, 'admin_id' => $status === Karya::MENUNGGU ? null : $admin?->id,
            'published_at' => $status === Karya::DISETUJUI ? now()->subDays(mt_rand(1, 90))->subMinutes(mt_rand(0, 1439)) : null,
        ]);

        $items = [];
        foreach ($foto as $path) {
            $items[] = new UploadedFile($path, basename($path), null, null, true);
        }
        $tmp = [];
        for ($n = 0; $n < $jumlah; $n++) {
            $w = 1200;
            $h = [900, 1200, 750][($karya->id + $n) % 3];
            $seed = $karya->id * 7 + $n;
            $path = $this->ambilFoto($slugKat, $query, $seed, $w, $h);
            $path ??= $this->seni(self::GAYA[$slugKat], $palet, $w, $h, $seed);
            $tmp[] = $path;
            $items[] = new UploadedFile($path, "demo-{$karya->id}-{$n}.jpg", 'image/jpeg', null, true);
        }

        $written = [];
        $uploader->store($karya, $items, 0, $written);
        foreach ($tmp as $t) {
            @unlink($t); // hanya berkas sementara; foto asli milik kreator tidak pernah dihapus
        }

        return $karya;
    }

    /** Gambar buatan (GD) dengan beberapa gaya supaya galeri tidak seragam. Deterministik per $seed. */
    private function seni(string $gaya, array $palet, int $w, int $h, int $seed): string
    {
        $state = (($seed + 1) * 48271) % 2147483647;
        $rand = function (int $min, int $max) use (&$state): int {
            $state = ($state * 48271) % 2147483647;

            return $min + $state % ($max - $min + 1);
        };

        [$a, $b] = $palet;
        if ($seed % 2) {
            [$a, $b] = [$b, $a];
        }
        $mix = fn (float $t): array => [
            (int) ($a[0] + ($b[0] - $a[0]) * $t), (int) ($a[1] + ($b[1] - $a[1]) * $t), (int) ($a[2] + ($b[2] - $a[2]) * $t),
        ];

        $img = imagecreatetruecolor($w, $h);
        $warna = fn (array $rgb, int $alpha): int => imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $alpha);

        for ($y = 0; $y < $h; $y++) {
            $c = $mix($y / $h);
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, $c[0], $c[1], $c[2]));
        }

        switch ($gaya) {
            case 'lingkaran':
                $jumlah = $rand(8, 14);
                for ($i = 0; $i < $jumlah; $i++) {
                    $d = $rand((int) ($w * 0.12), (int) ($w * 0.5));
                    $isi = $rand(0, 1) ? [255, 255, 255] : $mix($rand(0, 100) / 100);
                    imagefilledellipse($img, $rand(0, $w), $rand(0, $h), $d, $d, $warna($isi, $rand(85, 112)));
                }
                break;

            case 'gelombang':
                for ($i = 0; $i < 6; $i++) {
                    $dasar = (int) ($h * (0.25 + $i * 0.13));
                    $amp = $rand(20, 70);
                    $fase = $rand(0, 628) / 100;
                    $k = 2 * M_PI / $rand(300, 700);
                    $pts = [];
                    for ($x = 0; $x <= $w + 24; $x += 24) {
                        $pts[] = $x;
                        $pts[] = (int) ($dasar + $amp * sin($x * $k + $fase));
                    }
                    array_push($pts, $w + 24, $h, 0, $h);
                    imagefilledpolygon($img, $pts, $warna($mix(($i + 1) / 6), 50 + $i * 8));
                }
                break;

            case 'gunung':
                imagefilledellipse($img, (int) ($w * 0.7), (int) ($h * 0.3), (int) ($w * 0.22), (int) ($w * 0.22), $warna([255, 255, 255], 70));
                $langkah = (int) ($w / 8);
                for ($i = 0; $i < 4; $i++) {
                    $dasar = (int) ($h * (0.5 + $i * 0.12));
                    $pts = [0, $h];
                    for ($x = 0; $x <= $w; $x += $langkah) {
                        array_push($pts, $x, $dasar - $rand(0, (int) ($h * 0.18)));
                    }
                    array_push($pts, $w, $h);
                    imagefilledpolygon($img, $pts, $warna($mix(1 - $i * 0.25), 35 + $i * 10));
                }
                break;

            case 'garis':
                $lebar = $rand(40, 90);
                for ($x = -$h; $x < $w; $x += $lebar * 2) {
                    imagefilledpolygon($img, [$x, $h, $x + $lebar, $h, $x + $lebar + $h, 0, $x + $h, 0], $warna([255, 255, 255], 110));
                }
                break;

            case 'titik':
                $jarak = $rand(56, 96);
                for ($yy = (int) ($jarak / 2); $yy < $h; $yy += $jarak) {
                    for ($xx = (int) ($jarak / 2); $xx < $w; $xx += $jarak) {
                        $rad = (int) ($jarak * (0.12 + 0.3 * (($xx / $w + $yy / $h) / 2)));
                        imagefilledellipse($img, $xx, $yy, $rad * 2, $rad * 2, $warna([255, 255, 255], 95));
                    }
                }
                break;

            default: // gradien dengan dua lingkaran lembut
                $lembut = $warna([255, 255, 255], 95);
                imagefilledellipse($img, (int) ($w * 0.7), (int) ($h * 0.35), (int) ($w * 0.45), (int) ($w * 0.45), $lembut);
                imagefilledellipse($img, (int) ($w * 0.25), (int) ($h * 0.75), (int) ($w * 0.3), (int) ($w * 0.3), $lembut);
        }

        $file = tempnam(sys_get_temp_dir(), 'kgdemo');
        imagejpeg($img, $file, 88);
        imagedestroy($img);

        return $file;
    }
}

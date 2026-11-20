<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Karya;
use App\Models\Kategori;
use App\Models\Like;
use App\Models\Portofolio;
use App\Models\User;
use App\Services\MediaUploader;
use Database\Seeders\Concerns\MengambilFoto;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Menambah sekitar 60 karya Fotografi (lanskap, kuliner, jalanan/arsitektur, potret, makro) untuk kreator
 * yang sudah ada. Aman dijalankan di atas data yang sudah ada:
 *
 *   php artisan db:seed --class=FotografiTambahanSeeder
 *
 * Setiap karya memakai foto online sesuai kata kunci judulnya (butuh PIXABAY_KEY di .env; lihat
 * Concerns\MengambilFoto). Karya yang fotonya gagal diunduh dilewati, bukan diganti gambar palsu.
 * Kreator yang belum ada (mis. KontenTambahanSeeder belum dijalankan) dilewati. JANGAN dijalankan di produksi.
 */
class FotografiTambahanSeeder extends Seeder
{
    use MengambilFoto;

    private const KOMENTAR = [
        'Pengambilan sudutnya bagus banget.',
        'Cahaya dan warnanya natural, suka!',
        'Fokus dan ketajamannya mantap.',
        'Boleh tahu kamera dan lensa yang dipakai?',
        'Suasananya dapat, seperti ikut berada di sana.',
        'Framing-nya rapi, mata langsung tertuju ke subjek.',
        'Golden hour memang tidak pernah gagal.',
        'Warnanya tidak berlebihan, enak dilihat.',
        'Ini diambil jam berapa, Kak?',
        'Komposisinya sederhana tapi kuat.',
        'Salut, momennya pas sekali.',
        'Cocok banget dicetak besar dan dipajang.',
    ];

    public function run(MediaUploader $uploader): void
    {
        if (Karya::where('judul', 'Danau di Pagi Berkabut')->exists()) {
            return; // idempoten: jangan menggandakan karya
        }

        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $kategori = Kategori::where('slug', 'fotografi')->firstOrFail();
        $pelaku = User::whereIn('role', [User::ROLE_PENGUNJUNG, User::ROLE_KREATOR])->get();
        mt_srand(4242);

        foreach ($this->data() as $email => $daftar) {
            $owner = User::where('email', $email)->first();
            if (! $owner) {
                continue;
            }

            $milik = [];
            foreach ($daftar as $n => [$judul, $deskripsi, $query]) {
                $karya = $this->buat($uploader, $owner, $kategori->id, $admin, $judul, $deskripsi, $query, $n % 4 === 1 ? 3 : 1);
                if (! $karya) {
                    continue;
                }
                $milik[] = $karya;

                $calon = $pelaku->where('id', '!=', $owner->id)->shuffle()->values();
                foreach ($calon->take(mt_rand(1, 12)) as $u) {
                    Like::firstOrCreate(['user_id' => $u->id, 'karya_id' => $karya->id]);
                }
                if (mt_rand(0, 9) < 7) {
                    foreach ($calon->take(mt_rand(1, 3)) as $u) {
                        $this->komentar($u, $karya, self::KOMENTAR[mt_rand(0, count(self::KOMENTAR) - 1)]);
                    }
                }
            }

            if ($milik) {
                $porto = Portofolio::create([
                    'user_id' => $owner->id, 'judul' => 'Koleksi Fotografi '.$owner->name,
                    'deskripsi' => 'Kumpulan foto pilihan.', 'is_public' => true,
                ]);
                $porto->karyas()->sync(collect($milik)->pluck('id'));
            }
        }
    }

    private function komentar(User $user, Karya $karya, string $isi): void
    {
        $waktu = $karya->published_at->copy()->addHours(mt_rand(1, 300));
        if ($waktu->isFuture()) {
            $waktu = now()->subMinutes(mt_rand(5, 600));
        }

        $c = new Comment(['user_id' => $user->id, 'karya_id' => $karya->id, 'isi_komentar' => $isi]);
        $c->created_at = $waktu;
        $c->updated_at = $waktu;
        $c->save();
    }

    /** Unduh foto dulu; karya baru dibuat bila minimal satu foto berhasil. */
    private function buat(MediaUploader $uploader, User $owner, int $kategoriId, User $admin, string $judul, string $deskripsi, string $query, int $jumlah): ?Karya
    {
        $seedDasar = crc32($judul) % 100000;
        $tmp = [];
        for ($n = 0; $n < $jumlah; $n++) {
            $h = [900, 1200, 750][($seedDasar + $n) % 3];
            $path = $this->ambilFoto('fotografi', $query, $seedDasar + $n, 1200, $h);
            if ($path) {
                $tmp[] = $path;
            }
        }
        if (! $tmp) {
            return null;
        }

        $karya = Karya::create([
            'user_id' => $owner->id, 'kategori_id' => $kategoriId, 'judul' => $judul, 'deskripsi' => $deskripsi,
            'status' => Karya::DISETUJUI, 'admin_id' => $admin->id,
            'published_at' => now()->subDays(mt_rand(1, 90))->subMinutes(mt_rand(0, 1439)),
        ]);

        $items = [];
        foreach ($tmp as $i => $path) {
            $items[] = new UploadedFile($path, "foto-{$karya->id}-{$i}.jpg", 'image/jpeg', null, true);
        }

        $written = [];
        try {
            $uploader->store($karya, $items, 0, $written);
        } catch (\Throwable) {
            $uploader->deleteFiles($written);
            $karya->delete();
            $karya = null;
        }
        foreach ($tmp as $t) {
            @unlink($t);
        }

        return $karya;
    }

    /** @return array<string, array<int, array{0: string, 1: string, 2: string}>> email => [judul, deskripsi, kata kunci foto] */
    private function data(): array
    {
        return [
            'fajar@kreatifgallery.test' => [
                ['Danau di Pagi Berkabut', 'Permukaan danau yang tenang tertutup kabut tipis menjelang matahari terbit.', 'lake morning fog'],
                ['Air Terjun Tersembunyi', 'Air terjun kecil di tengah hutan, difoto dengan rana lambat agar airnya halus seperti sutra.', 'waterfall jungle'],
                ['Jalan Menuju Pegunungan', 'Jalan berkelok yang menanjak ke arah pegunungan di kejauhan.', 'mountain road'],
                ['Padang Rumput Saat Senja', 'Rumput liar bergoyang tertiup angin dalam cahaya emas sore hari.', 'meadow sunset'],
                ['Pulau Karang Biru', 'Gugusan pulau karang dengan air laut biru jernih dilihat dari ketinggian.', 'tropical island aerial'],
                ['Candi di Balik Fajar', 'Siluet candi kuno dengan langit yang perlahan berubah jingga.', 'temple sunrise'],
                ['Tebing dan Ombak', 'Ombak besar menghantam tebing karang di tepi pantai selatan.', 'cliff waves'],
                ['Hutan Pinus Berembun', 'Barisan pohon pinus diselimuti kabut pagi yang lembap.', 'pine forest fog'],
                ['Bintang di Atas Perkemahan', 'Langit malam penuh bintang dengan tenda kecil bercahaya di bawahnya.', 'milky way tent'],
                ['Teluk dari Atas Bukit', 'Pemandangan teluk melengkung dari puncak bukit saat cuaca cerah.', 'bay viewpoint'],
                ['Pelabuhan Kecil', 'Perahu-perahu kecil bersandar di pelabuhan desa nelayan.', 'small harbor boats'],
                ['Gunung Api dari Kejauhan', 'Gunung api berdiri megah di balik hamparan awan.', 'volcano'],
            ],
            'rahmi@kreatifgallery.test' => [
                ['Semangkuk Soto Hangat', 'Soto berkuah bening dengan uap tipis, disajikan di mangkuk keramik sederhana.', 'soup bowl'],
                ['Roti Panggang dan Selai', 'Roti panggang renyah dengan selai buah, difoto dengan cahaya jendela pagi.', 'toast jam'],
                ['Es Cendol Segar', 'Es cendol dengan santan dan gula merah, pelepas dahaga di siang yang terik.', 'iced dessert coconut'],
                ['Pisang Goreng Keemasan', 'Pisang goreng yang baru diangkat, renyah di luar dan lembut di dalam.', 'fried banana'],
                ['Meja Sarapan Keluarga', 'Meja sarapan sederhana dengan roti, buah, dan teh untuk satu keluarga.', 'breakfast table'],
                ['Kopi Hitam dan Buku', 'Secangkir kopi hitam di samping buku yang terbuka, suasana akhir pekan yang tenang.', 'black coffee book'],
                ['Sayur Pasar Pagi', 'Tumpukan sayur segar yang baru diantar ke pasar pagi hari.', 'vegetables market'],
                ['Teh Melati di Teko Kaca', 'Teh melati diseduh dalam teko kaca, bunganya mekar perlahan.', 'jasmine tea glass'],
                ['Kue Lapis Warna-Warni', 'Kue lapis dengan lapisan warna cerah yang rapi dan kenyal.', 'layered cake colorful'],
                ['Ikan Bakar di Atas Daun', 'Ikan bakar dengan bumbu rempah disajikan di atas daun pisang.', 'grilled fish'],
            ],
            'syahrial@kreatifgallery.test' => [
                ['Atap Genteng Selepas Hujan', 'Deretan atap genteng yang basah memantulkan langit mendung.', 'rooftops rain'],
                ['Tangga Spiral Gedung Tua', 'Tangga spiral besi di gedung tua, diambil dari bawah ke atas.', 'spiral staircase'],
                ['Jendela Kayu Biru', 'Jendela kayu bercat biru yang mulai pudar di dinding putih.', 'blue wooden window'],
                ['Becak di Persimpangan', 'Becak berhenti menunggu penumpang di persimpangan yang ramai.', 'rickshaw street'],
                ['Menara Masjid Saat Maghrib', 'Menara masjid berlatar langit senja yang berwarna ungu kemerahan.', 'mosque minaret dusk'],
                ['Pantulan di Genangan Air', 'Gedung kota terpantul terbalik di genangan air setelah hujan.', 'puddle reflection city'],
                ['Kabel dan Langit Senja', 'Garis-garis kabel listrik membelah langit jingga sore hari.', 'power lines sunset'],
                ['Pintu Tua Bercat Mengelupas', 'Pintu kayu tua dengan cat berlapis yang mengelupas, penuh cerita.', 'old door'],
                ['Gedung Kaca Memantulkan Awan', 'Fasad gedung kaca yang memantulkan awan dan langit biru.', 'glass building clouds'],
                ['Warung di Sudut Jalan', 'Warung makan kecil di sudut jalan yang selalu ramai saat jam makan siang.', 'street food stall'],
                ['Siluet Pejalan di Jembatan', 'Siluet orang berjalan di jembatan dengan latar matahari terbenam.', 'silhouette bridge'],
                ['Bayangan Pagar Besi', 'Bayangan pagar besi membentuk pola garis di jalan beton.', 'iron fence shadow'],
            ],
            'muklis@kreatifgallery.test' => [
                ['Senyum Penjual Pasar', 'Potret penjual sayur yang tersenyum di antara dagangannya.', 'market vendor portrait'],
                ['Tangan Penenun', 'Tangan penenun menggerakkan benang di atas alat tenun tradisional.', 'weaver hands'],
                ['Anak-Anak Bermain di Pantai', 'Anak-anak berlarian dan tertawa di pantai saat sore hari.', 'children playing beach'],
                ['Nenek dan Kain Tenun', 'Potret seorang nenek memegang kain tenun hasil karyanya.', 'elderly woman traditional'],
                ['Penjahit di Tengah Kerja', 'Penjahit menunduk di mesin jahit, dikelilingi gulungan kain.', 'tailor sewing'],
                ['Tukang Kayu dan Serutan', 'Serutan kayu beterbangan saat tukang kayu menghaluskan papan.', 'carpenter wood shavings'],
                ['Pandai Besi dan Percikan Api', 'Percikan api memancar saat pandai besi menempa logam panas.', 'blacksmith sparks'],
                ['Petani di Pematang', 'Petani berjalan di pematang sawah dengan caping di kepala.', 'farmer rice field'],
                ['Penjual Gorengan Sore', 'Penjual gorengan menyiapkan dagangan di gerobak menjelang sore.', 'street vendor evening'],
                ['Potret Perajin Tembikar', 'Perajin tembikar dengan tangan berlumur tanah liat menatap kamera.', 'potter portrait'],
            ],
            'raka@kreatifgallery.test' => [
                ['Pejalan Kaki di Zebra Cross', 'Orang-orang menyeberang serempak di zebra cross yang padat.', 'crosswalk people'],
                ['Lampu Merah Hujan Malam', 'Lampu kendaraan memantul di jalan basah pada malam hujan.', 'rainy night traffic'],
                ['Musisi Jalanan', 'Musisi jalanan memainkan gitar di trotoar yang ramai.', 'street musician'],
                ['Stasiun Pukul Enam', 'Penumpang menunggu kereta di peron stasiun pagi hari.', 'train station morning'],
                ['Payung Warna-Warni', 'Payung warna-warni terbuka di tengah gerimis kota.', 'colorful umbrellas'],
                ['Penjual Balon', 'Penjual balon dengan rangkaian balon warna-warni di taman kota.', 'balloon seller'],
                ['Kafe Kecil di Sudut Kota', 'Kafe mungil dengan lampu hangat di sudut jalan yang sepi.', 'small cafe corner'],
                ['Bus Kota Menjelang Malam', 'Bus kota melaju dengan lampu menyala di senja yang meredup.', 'city bus night'],
                ['Langit Merah di Atas Kota', 'Langit kemerahan di atas siluet gedung-gedung kota.', 'red sky city skyline'],
                ['Pasar Loak', 'Barang bekas dan antik tertata di lapak pasar loak akhir pekan.', 'flea market'],
            ],
            'kreator@kreatifgallery.test' => [
                ['Kupu-Kupu di Atas Bunga', 'Kupu-kupu hinggap di bunga, difoto jarak dekat dengan latar buram.', 'butterfly flower'],
                ['Kumbang dan Embun', 'Kumbang kecil di ranting yang dipenuhi tetes embun.', 'beetle macro'],
                ['Bunga Liar di Tepi Jalan', 'Bunga liar yang tumbuh di tepi jalan, nyaris tak terlihat.', 'wildflowers'],
                ['Burung Camar di Pelabuhan', 'Burung camar bertengger di tiang dermaga saat pagi.', 'seagull'],
                ['Sarang Laba-Laba Berembun', 'Jaring laba-laba berhias butir embun yang berkilau.', 'spider web dew'],
                ['Daun Jatuh di Air', 'Daun kering jatuh di permukaan air dan membentuk riak halus.', 'leaf water ripple'],
            ],
        ];
    }
}

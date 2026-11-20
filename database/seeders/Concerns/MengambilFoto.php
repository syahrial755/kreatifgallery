<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * Mengambil foto untuk data demo dari internet: Pixabay (butuh PIXABAY_KEY di .env, dicari per kata kunci
 * karya agar foto sesuai judul) lalu Picsum (tanpa kunci) sebagai cadangan. Mengembalikan null bila gagal
 * atau dimatikan, sehingga pemanggil memakai gambar buatan. Tidak pernah mengunduh saat menjalankan tes.
 * Matikan dengan SEED_FOTO_ONLINE=false.
 */
trait MengambilFoto
{
    /** Jadi false setelah unduhan pertama gagal (mis. offline) supaya tidak menunggu timeout berulang kali. */
    private bool $fotoOnline = true;

    /** @var array<string, string[]> URL hasil pencarian Pixabay, per "tipe|kata kunci" */
    private array $cacheFoto = [];

    protected function fotoAktif(): bool
    {
        return $this->fotoOnline
            && ! app()->runningUnitTests()
            && filter_var(env('SEED_FOTO_ONLINE', true), FILTER_VALIDATE_BOOLEAN);
    }

    private function tipeFoto(string $slugKat): string
    {
        return match ($slugKat) {
            'ilustrasi' => 'illustration',
            'desain-grafis' => 'all',
            default => 'photo',
        };
    }

    private function queryKategori(string $slugKat): string
    {
        return [
            'fotografi' => 'landscape nature', 'ilustrasi' => 'colorful art', 'desain-grafis' => 'poster design',
            'seni-rupa' => 'painting art', 'kriya' => 'handmade craft',
        ][$slugKat] ?? 'creative';
    }

    /** Unduh gambar ke berkas sementara; null bila gagal / dimatikan. Berkas harus benar-benar gambar. */
    protected function unduhUrl(string $url): ?string
    {
        if (! $this->fotoAktif()) {
            return null;
        }

        try {
            $res = Http::timeout(20)->get($url);
            if (! $res->successful() || strlen($res->body()) < 3000) {
                return null;
            }
            $file = tempnam(sys_get_temp_dir(), 'kgfoto');
            file_put_contents($file, $res->body());
            if (@getimagesize($file) === false) {
                @unlink($file);

                return null;
            }

            return $file;
        } catch (\Throwable) {
            $this->fotoOnline = false; // jaringan bermasalah: sisanya langsung pakai gambar buatan

            return null;
        }
    }

    /** @return string[] URL gambar hasil pencarian Pixabay (di-cache per kata kunci) */
    private function cariPixabay(string $query, string $tipe): array
    {
        $kunci = (string) env('PIXABAY_KEY', '');
        $k = $tipe.'|'.$query;
        if (isset($this->cacheFoto[$k])) {
            return $this->cacheFoto[$k];
        }
        $this->cacheFoto[$k] = [];
        if ($kunci === '' || ! $this->fotoAktif()) {
            return [];
        }

        try {
            for ($coba = 0; $coba < 2; $coba++) {
                $res = Http::timeout(20)->get('https://pixabay.com/api/', [
                    'key' => $kunci, 'q' => $query, 'image_type' => $tipe,
                    'min_width' => 1000, 'per_page' => 200, 'safesearch' => 'true',
                ]);
                if ($res->status() === 429) { // batas Pixabay: 100 permintaan / 60 detik
                    sleep(61);

                    continue;
                }
                if ($res->successful()) {
                    $this->cacheFoto[$k] = collect($res->json('hits', []))->pluck('largeImageURL')->filter()->values()->all();
                }
                $sisa = $res->header('X-RateLimit-Remaining');
                if ($sisa !== '' && (int) $sisa <= 1) {
                    sleep(max(1, (int) $res->header('X-RateLimit-Reset')) + 1);
                }
                break;
            }
        } catch (\Throwable) {
            $this->fotoOnline = false;
        }

        return $this->cacheFoto[$k];
    }

    /**
     * Foto untuk satu gambar karya. $query = kata kunci Inggris yang menggambarkan judul karya;
     * bila kosong/tanpa hasil dipakai kata kunci umum kategori, lalu Picsum.
     */
    protected function ambilFoto(string $slugKat, ?string $query, int $seed, int $w, int $h): ?string
    {
        if (! $this->fotoAktif()) {
            return null;
        }

        $tipe = $this->tipeFoto($slugKat);
        $daftar = $query ? $this->cariPixabay($query, $tipe) : [];
        if (! $daftar) {
            $daftar = $this->cariPixabay($this->queryKategori($slugKat), $tipe);
        }

        return $this->unduhUrl($daftar ? $daftar[$seed % count($daftar)] : "https://picsum.photos/seed/kg{$seed}/{$w}/{$h}.jpg");
    }
}

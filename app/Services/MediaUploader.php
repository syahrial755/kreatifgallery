<?php

namespace App\Services;

use App\Exceptions\MediaProcessingException;
use App\Models\Karya;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Diagram Sequence langkah 6-11: simpan file -> karya/, buat thumbnail WebP (hanya gambar), INSERT media.
 *
 * Path semua file yang sudah tertulis dikumpulkan lewat parameter $written (by reference) supaya pemanggil
 * dapat menghapusnya bila transaksi gagal -> tidak ada file yatim.
 */
class MediaUploader
{
    private function disk()
    {
        return Storage::disk(config('kreatifgallery.upload.disk'));
    }

    /**
     * @param  UploadedFile[]  $files
     * @param  string[]  $written
     */
    public function store(Karya $karya, array $files, int $startOrder, array &$written): void
    {
        $order = $startOrder;
        foreach ($files as $file) {
            $this->storeOne($karya, $file, $order++, $written);
        }
    }

    private function storeOne(Karya $karya, UploadedFile $file, int $order, array &$written): Media
    {
        $mime = (string) $file->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $dir = config('kreatifgallery.upload.dir').'/'.$karya->id;

        $path = $file->storeAs($dir, Str::uuid().'.'.$ext, config('kreatifgallery.upload.disk'));
        if (! $path) {
            throw new MediaProcessingException('File gagal disimpan.');
        }
        $written[] = $path;

        $thumb = null;
        if (! $isVideo) {
            $thumb = $this->makeThumbnail($file->getRealPath(), $dir.'/thumbs/'.Str::uuid().'.webp');
            if ($thumb) {
                $written[] = $thumb;
            }
        }

        return Media::create([
            'karya_id' => $karya->id,
            'file_path' => $path,
            'thumb_path' => $thumb,
            'tipe_media' => $isVideo ? 'video' : 'gambar',
            'urutan' => $order,
        ]);
    }

    /**
     * Thumbnail WebP lebar 720px (GD). Gambar yang lebih kecil dari 720px tidak diperbesar.
     * Mengembalikan null bila GD tidak mendukung WebP (kartu akan memakai file asli).
     */
    public function makeThumbnail(string $sourcePath, string $targetPath): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $img = @imagecreatefromstring((string) file_get_contents($sourcePath));
        if (! $img) {
            throw new MediaProcessingException('File gambar tidak dapat dibaca (rusak atau tidak didukung).');
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $tw = min((int) config('kreatifgallery.upload.thumb_width'), $w);
        $th = max(1, (int) round($h * $tw / $w));

        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $tw, $th, $w, $h);

        ob_start();
        imagewebp($dst, null, 82);
        $binary = (string) ob_get_clean();
        imagedestroy($img);
        imagedestroy($dst);

        if ($binary === '' || ! $this->disk()->put($targetPath, $binary)) {
            throw new MediaProcessingException('Thumbnail gagal dibuat.');
        }

        return $targetPath;
    }

    /** @param  array<int, string|null>  $paths */
    public function deleteFiles(array $paths): void
    {
        $paths = array_values(array_filter($paths));
        if ($paths) {
            $this->disk()->delete($paths);
        }
    }

    public function deleteDirectory(Karya $karya): void
    {
        $this->disk()->deleteDirectory(config('kreatifgallery.upload.dir').'/'.$karya->id);
    }
}

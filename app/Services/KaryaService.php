<?php

namespace App\Services;

use App\Models\Karya;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Logika bisnis karya sesuai diagram Activity "Publikasi Karya" & Sequence "Mengunggah Karya".
 * Semua perubahan DB dalam satu transaksi; file yang sudah tertulis dibersihkan bila transaksi gagal,
 * dan file lama baru dihapus SETELAH commit (tidak ada file yatim maupun rekaman tanpa file).
 */
class KaryaService
{
    public function __construct(
        private MediaUploader $uploader,
        private NotificationService $notifications,
    ) {
    }

    /** @param  UploadedFile[]  $files */
    public function create(User $user, array $data, array $files): Karya
    {
        $written = [];

        try {
            $karya = DB::transaction(function () use ($user, $data, $files, &$written) {
                $karya = Karya::create([
                    'user_id' => $user->id,
                    'kategori_id' => $data['kategori_id'],
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'status' => Karya::MENUNGGU, // langkah 4 Sequence: status = 'menunggu'
                ]);
                $this->uploader->store($karya, $files, 0, $written);

                return $karya;
            });
        } catch (Throwable $e) {
            $this->uploader->deleteFiles($written);
            throw $e;
        }

        $this->notifications->karyaSubmitted($karya);

        return $karya;
    }

    /**
     * @param  UploadedFile[]  $newFiles
     * @param  int[]  $removeIds  id media milik karya ini yang akan dihapus
     */
    public function update(Karya $karya, array $data, array $newFiles = [], array $removeIds = []): Karya
    {
        $written = [];
        $toDelete = [];
        $wasStatus = $karya->status;
        $resubmitted = false;

        try {
            DB::transaction(function () use ($karya, $data, $newFiles, $removeIds, &$written, &$toDelete, $wasStatus, &$resubmitted) {
                $karya->fill([
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'kategori_id' => $data['kategori_id'],
                ]);

                $remove = $karya->medias()->whereIn('id', $removeIds)->get();
                $nextOrder = ((int) $karya->medias()->max('urutan')) + 1;

                $this->uploader->store($karya, $newFiles, $nextOrder, $written);

                foreach ($remove as $media) {
                    $toDelete[] = $media->file_path;
                    $toDelete[] = $media->thumb_path;
                    $media->delete();
                }

                $mediaChanged = count($newFiles) > 0 || $remove->isNotEmpty();

                // Activity: revisi -> status otomatis kembali 'menunggu'.
                // Catatan: karya tayang yang medianya diubah juga kembali 'menunggu' (tidak publik selama re-moderasi).
                if ($wasStatus === Karya::DITOLAK || ($wasStatus === Karya::DISETUJUI && $mediaChanged)) {
                    $karya->status = Karya::MENUNGGU;
                    $karya->published_at = null;
                    $resubmitted = true;
                }

                $karya->save();
            });
        } catch (Throwable $e) {
            $this->uploader->deleteFiles($written);
            throw $e;
        }

        $this->uploader->deleteFiles($toDelete); // setelah commit

        if ($resubmitted) {
            $this->notifications->revisionSubmitted($karya, $wasStatus === Karya::DISETUJUI);
        }

        return $karya;
    }

    public function delete(Karya $karya): void
    {
        DB::transaction(function () use ($karya) {
            // Laporan atas komentar karya ini tidak ikut cascade (nullOnDelete); tutup agar tidak yatim di antrian admin.
            \App\Models\Report::whereIn('komentar_id', $karya->comments()->select('id'))
                ->where('status', \App\Models\Report::BARU)
                ->update(['status' => \App\Models\Report::SELESAI, 'tindakan' => 'hapus_komentar', 'handled_at' => now(), 'updated_at' => now()]);
            $karya->delete(); // media, suka, komentar, laporan karya ikut terhapus (cascade)
        });
        $this->uploader->deleteDirectory($karya);   // setelah commit
    }
}

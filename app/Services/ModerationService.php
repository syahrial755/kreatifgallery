<?php

namespace App\Services;

use App\Models\Karya;
use App\Models\Report;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Moderasi sesuai diagram Activity "Publikasi Karya" (cabang Admin) dan DFD (keputusan moderasi,
 * tindak lanjut laporan). Semua perubahan status dilakukan dalam transaksi dengan row lock supaya dua admin
 * yang menekan tombol bersamaan tidak menghasilkan keputusan ganda. Notifikasi dikirim setelah commit.
 *
 * Pelanggaran aturan alur melempar DomainException (pesan siap tampil ke admin).
 */
class ModerationService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /** Setujui: status disetujui, catat admin_id, tayang (published_at), notifikasi 'disetujui'. */
    public function approve(Karya $karya, User $admin): Karya
    {
        $karya = DB::transaction(function () use ($karya, $admin) {
            $k = Karya::whereKey($karya->id)->lockForUpdate()->firstOrFail();
            if ($k->status !== Karya::MENUNGGU) {
                throw new DomainException('Karya ini sudah dimoderasi (status: '.$k->status.').');
            }
            if (! $k->medias()->exists()) {
                throw new DomainException('Karya tanpa media tidak dapat disetujui.');
            }
            $k->update([
                'status' => Karya::DISETUJUI, 'admin_id' => $admin->id,
                'published_at' => now(), 'catatan_moderasi' => null,
            ]);

            return $k;
        });

        $this->notifications->approved($karya);

        return $karya;
    }

    /** Tolak: catatan wajib, status ditolak, notifikasi 'ditolak' + catatan. */
    public function reject(Karya $karya, User $admin, string $note): Karya
    {
        $note = trim($note);
        if ($note === '') {
            throw new DomainException('Catatan penolakan wajib diisi.');
        }

        $karya = DB::transaction(function () use ($karya, $admin, $note) {
            $k = Karya::whereKey($karya->id)->lockForUpdate()->firstOrFail();
            if ($k->status !== Karya::MENUNGGU) {
                throw new DomainException('Karya ini sudah dimoderasi (status: '.$k->status.').');
            }
            $k->update(['status' => Karya::DITOLAK, 'admin_id' => $admin->id, 'published_at' => null, 'catatan_moderasi' => $note]);

            return $k;
        });

        $this->notifications->rejected($karya);

        return $karya;
    }

    /**
     * Turunkan karya yang sudah tayang (DFD: "turunkan karya"). Karya kembali ke status 'ditolak' dengan catatan,
     * sehingga kreator dapat merevisi dan mengajukan ulang. Semua laporan terbuka atas karya ini ditutup.
     */
    public function unpublish(Karya $karya, User $admin, string $reason): Karya
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Alasan penurunan karya wajib diisi.');
        }

        $karya = DB::transaction(function () use ($karya, $admin, $reason) {
            $k = Karya::whereKey($karya->id)->lockForUpdate()->firstOrFail();
            if ($k->status !== Karya::DISETUJUI) {
                throw new DomainException('Hanya karya yang sedang tayang yang dapat diturunkan.');
            }
            $k->update([
                'status' => Karya::DITOLAK, 'admin_id' => $admin->id, 'published_at' => null,
                'catatan_moderasi' => 'Diturunkan oleh admin: '.$reason,
            ]);
            $this->closeReports(Report::where('karya_id', $k->id), $admin, 'turunkan_karya');

            return $k;
        });

        $this->notifications->rejected($karya);

        return $karya;
    }

    /** Tangani laporan: abaikan | hapus_komentar | turunkan_karya. */
    public function handleReport(Report $report, User $admin, string $tindakan, ?string $note = null): Report
    {
        return DB::transaction(function () use ($report, $admin, $tindakan, $note) {
            $r = Report::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($r->status === Report::SELESAI) {
                throw new DomainException('Laporan ini sudah ditangani.');
            }

            switch ($tindakan) {
                case 'abaikan':
                    $this->closeReports(Report::whereKey($r->id), $admin, 'abaikan');
                    break;

                case 'hapus_komentar':
                    $comment = $r->komentar;
                    if (! $comment) {
                        throw new DomainException('Laporan ini tidak menyangkut komentar (atau komentar sudah dihapus).');
                    }
                    // Tutup semua laporan atas komentar ini SEBELUM dihapus; FK nullOnDelete menjaga catatan laporan.
                    $this->closeReports(Report::where('komentar_id', $comment->id), $admin, 'hapus_komentar');
                    $comment->delete();
                    break;

                case 'turunkan_karya':
                    if (! $r->karya_id) {
                        throw new DomainException('Laporan ini tidak menyangkut karya.');
                    }
                    $this->unpublish($r->karya, $admin, (string) $note);
                    break;

                default:
                    throw new DomainException('Tindakan tidak dikenal.');
            }

            return $r->refresh();
        });
    }

    /** Dipanggil sebelum komentar dihapus (oleh pemilik/admin): laporan terbuka atasnya ditutup agar tidak yatim. */
    public function closeCommentReports(\App\Models\Comment $comment, User $by): void
    {
        $this->closeReports(Report::where('komentar_id', $comment->id), $by, 'hapus_komentar');
    }

    private function closeReports($query, User $admin, string $tindakan): void
    {
        $query->where('status', Report::BARU)->update([
            'status' => Report::SELESAI, 'tindakan' => $tindakan,
            'handled_by' => $admin->id, 'handled_at' => now(), 'updated_at' => now(),
        ]);
    }
}

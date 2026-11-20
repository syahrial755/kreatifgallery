<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Karya;
use App\Notifications\AppNotification;
use Illuminate\Support\Str;

class NotificationService
{
    public function karyaSubmitted(Karya $karya): void
    {
        $karya->user->notify(new AppNotification(
            'submitted',
            "Karya \"{$karya->judul}\" berhasil diunggah dan menunggu moderasi admin.",
            route('kreator.karya.index', ['status' => Karya::MENUNGGU]),
        ));
    }

    public function revisionSubmitted(Karya $karya, bool $wasPublished): void
    {
        $why = $wasPublished ? 'Media karya diubah, sehingga ditarik dari galeri dan' : 'Revisi karya terkirim dan';
        $karya->user->notify(new AppNotification(
            'revision',
            "{$why} menunggu moderasi ulang: \"{$karya->judul}\".",
            route('kreator.karya.index', ['status' => Karya::MENUNGGU]),
        ));
    }

    public function approved(Karya $karya): void
    {
        $karya->user->notify(new AppNotification(
            'approved',
            "Karya \"{$karya->judul}\" disetujui dan kini tampil di galeri.",
            route('karya.show', $karya),
        ));
    }

    public function rejected(Karya $karya): void
    {
        $karya->user->notify(new AppNotification(
            'rejected',
            "Karya \"{$karya->judul}\" ditolak. Catatan admin: ".Str::limit((string) $karya->catatan_moderasi, 200),
            route('kreator.karya.edit', $karya),
        ));
    }

    /** Pemilik karya diberi tahu; komentar pada karya sendiri tidak memicu notifikasi. */
    public function newComment(Comment $comment): void
    {
        $karya = $comment->karya;
        if ($karya->user_id === $comment->user_id) {
            return;
        }

        $karya->user->notify(new AppNotification(
            'comment',
            "{$comment->user->name} mengomentari \"{$karya->judul}\".",
            route('karya.show', $karya).'#komentar-'.$comment->id,
        ));
    }
}

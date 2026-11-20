<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi dalam aplikasi (tabel notifications). Tidak ada email/integrasi eksternal (catatan DFD).
 * kind: submitted | revision | approved | rejected | comment
 */
class AppNotification extends Notification
{
    public function __construct(
        public string $kind,
        public string $message,
        public ?string $url = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return ['kind' => $this->kind, 'message' => $this->message, 'url' => $this->url];
    }
}

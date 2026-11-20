<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const BARU = 'baru';
    public const SELESAI = 'selesai';

    protected $fillable = [
        'user_id', 'karya_id', 'komentar_id', 'alasan', 'snapshot', 'status',
        'tindakan', 'handled_by', 'handled_at',
    ];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function karya(): BelongsTo
    {
        return $this->belongsTo(Karya::class);
    }

    public function komentar(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'komentar_id');
    }

    public function penangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

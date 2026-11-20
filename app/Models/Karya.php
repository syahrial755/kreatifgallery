<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karya extends Model
{
    public const MENUNGGU = 'menunggu';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK = 'ditolak';

    protected $table = 'karyas';

    protected $fillable = [
        'user_id', 'kategori_id', 'admin_id', 'judul', 'deskripsi',
        'status', 'catatan_moderasi', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function medias(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('urutan');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function portofolios(): BelongsToMany
    {
        return $this->belongsToMany(Portofolio::class, 'karya_portofolio');
    }

    /** Hanya karya disetujui milik akun aktif yang tampil publik. */
    public function scopePublik(Builder $q): Builder
    {
        return $q->where('status', self::DISETUJUI)
            ->whereHas('user', fn ($u) => $u->where('is_active', true));
    }

    public function scopeStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    /** Konsisten dengan scopePublik: disetujui DAN pemilik masih aktif. */
    public function isPublik(): bool
    {
        return $this->status === self::DISETUJUI && (bool) $this->user?->is_active;
    }

    public function getCoverAttribute(): ?Media
    {
        return $this->medias->first();
    }
}

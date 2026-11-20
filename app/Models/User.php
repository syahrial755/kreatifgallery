<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_KREATOR = 'kreator';
    public const ROLE_PENGUNJUNG = 'pengunjung';

    protected $fillable = ['name', 'email', 'password', 'role', 'foto_profil', 'bio', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    // Default di memori agar model yang baru dibuat konsisten dengan default kolom DB.
    protected $attributes = [
        'role' => self::ROLE_PENGUNJUNG,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKreator(): bool
    {
        return $this->role === self::ROLE_KREATOR;
    }

    public function karyas(): HasMany
    {
        return $this->hasMany(Karya::class);
    }

    public function portofolios(): HasMany
    {
        return $this->hasMany(Portofolio::class);
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

    public function getFotoUrlAttribute(): string
    {
        if ($this->foto_profil) {
            return Storage::disk('public')->url($this->foto_profil);
        }

        // Avatar inisial berupa SVG inline (tanpa layanan eksternal: tetap tampil offline dan tidak membocorkan nama ke pihak ketiga).
        $parts = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#635BDB"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="26" font-weight="700" fill="#fff">'
            .htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->name)))->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }
}

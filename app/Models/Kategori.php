<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'kategoris';

    protected $fillable = ['admin_id', 'nama_kategori', 'slug', 'deskripsi'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function karyas(): HasMany
    {
        return $this->hasMany(Karya::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

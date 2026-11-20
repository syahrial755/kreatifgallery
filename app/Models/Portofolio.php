<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Portofolio extends Model
{
    protected $table = 'portofolios';

    protected $fillable = ['user_id', 'judul', 'deskripsi', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function karyas(): BelongsToMany
    {
        return $this->belongsToMany(Karya::class, 'karya_portofolio');
    }
}

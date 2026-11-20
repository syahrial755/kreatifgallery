<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = ['user_id', 'karya_id', 'isi_komentar'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function karya(): BelongsTo
    {
        return $this->belongsTo(Karya::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'komentar_id');
    }
}

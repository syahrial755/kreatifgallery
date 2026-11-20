<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'medias';

    protected $fillable = ['karya_id', 'file_path', 'thumb_path', 'tipe_media', 'urutan'];

    public function karya(): BelongsTo
    {
        return $this->belongsTo(Karya::class);
    }

    public function isVideo(): bool
    {
        return $this->tipe_media === 'video';
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getThumbUrlAttribute(): ?string
    {
        return $this->thumb_path ? Storage::disk('public')->url($this->thumb_path) : null;
    }
}

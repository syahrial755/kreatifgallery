<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Aturan validasi sesuai diagram Sequence (alt "Validasi gagal"): judul ≤150, kategori ada, 1–10 file, tipe & ukuran. */
class StoreKaryaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isKreator() ?? false;
    }

    public function rules(): array
    {
        $c = config('kreatifgallery.upload');

        return [
            'judul' => ['required', 'string', 'max:'.$c['max_title']],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
            'media' => ['required', 'array', 'min:'.$c['min_files'], 'max:'.$c['max_files']],
            'media.*' => [
                'required', 'file',
                'mimes:'.implode(',', array_merge($c['image_mimes'], $c['video_mimes'])),
                'mimetypes:'.implode(',', $c['mime_types']),
                'max:'.$c['max_size_kb'],
            ],
        ];
    }

    public function messages(): array
    {
        return self::pesan();
    }

    public static function pesan(): array
    {
        return [
            'judul.required' => 'Judul karya wajib diisi.',
            'judul.max' => 'Judul maksimal 150 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 5000 karakter.',
            'kategori_id.required' => 'Pilih kategori karya.',
            'kategori_id.exists' => 'Kategori tidak ditemukan.',
            'media.required' => 'Pilih minimal 1 file media.',
            'media.min' => 'Pilih minimal 1 file media.',
            'media.max' => 'Maksimal 10 file per karya.',
            'media.*.file' => 'Berkas tidak valid.',
            'media.*.mimes' => 'Format file harus JPG, PNG, WebP, GIF, MP4, atau WebM.',
            'media.*.mimetypes' => 'Format file harus JPG, PNG, WebP, GIF, MP4, atau WebM.',
            'media.*.max' => 'Ukuran setiap file maksimal 20 MB.',
            'media.*.uploaded' => 'File gagal diunggah. Periksa batas ukuran unggahan server (upload_max_filesize / post_max_size).',
        ];
    }
}

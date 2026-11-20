<?php

namespace App\Http\Requests;

use App\Models\Karya;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateKaryaRequest extends FormRequest
{
    /** Kreator hanya boleh mengubah karyanya sendiri (KaryaPolicy::update) -> 403 bila bukan pemilik. */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('karya'));
    }

    public function rules(): array
    {
        $c = config('kreatifgallery.upload');

        return [
            'judul' => ['required', 'string', 'max:'.$c['max_title']],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
            'media' => ['nullable', 'array', 'max:'.$c['max_files']],
            'media.*' => [
                'required', 'file',
                'mimes:'.implode(',', array_merge($c['image_mimes'], $c['video_mimes'])),
                'mimetypes:'.implode(',', $c['mime_types']),
                'max:'.$c['max_size_kb'],
            ],
            'hapus_media' => ['nullable', 'array'],
            'hapus_media.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return StoreKaryaRequest::pesan();
    }

    /** Total media setelah revisi harus 1–10. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Karya $karya */
            $karya = $this->route('karya');
            $c = config('kreatifgallery.upload');

            $existing = $karya->medias()->pluck('id');
            $remove = $existing->intersect($this->removeIds())->count();
            $total = $existing->count() - $remove + count($this->file('media', []));

            if ($total < $c['min_files']) {
                $v->errors()->add('media', 'Karya harus memiliki minimal 1 file media.');
            }
            if ($total > $c['max_files']) {
                $v->errors()->add('media', 'Total file media maksimal 10 per karya.');
            }
        });
    }

    /** @return int[] */
    public function removeIds(): array
    {
        return array_map('intval', (array) $this->input('hapus_media', []));
    }
}

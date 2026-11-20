<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi dilakukan oleh KaryaPolicy::interact di controller
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['isi_komentar' => is_string($this->isi_komentar) ? trim($this->isi_komentar) : $this->isi_komentar]);
    }

    public function rules(): array
    {
        return ['isi_komentar' => ['required', 'string', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['isi_komentar' => 'komentar'];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Catatan wajib untuk penolakan / penurunan karya (aturan bisnis: "Tolak karya + catatan wajib"). */
class ModerationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['catatan_moderasi' => ['required', 'string', 'min:5', 'max:1000']];
    }

    public function messages(): array
    {
        return [
            'catatan_moderasi.required' => 'Catatan wajib diisi.',
            'catatan_moderasi.min' => 'Catatan minimal 5 karakter agar bermanfaat bagi kreator.',
            'catatan_moderasi.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $kategori = $this->route('kategori');

        return [
            'nama_kategori' => ['required', 'string', 'max:80', Rule::unique('kategoris', 'nama_kategori')->ignore($kategori?->id)],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.max' => 'Nama kategori maksimal 80 karakter.',
            'nama_kategori.unique' => 'Nama kategori sudah dipakai.',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter.',
        ];
    }
}

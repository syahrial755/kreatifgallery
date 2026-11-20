<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi dilakukan di controller
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['alasan' => is_string($this->alasan) ? trim($this->alasan) : $this->alasan]);
    }

    public function rules(): array
    {
        return ['alasan' => ['required', 'string', 'min:10', 'max:1000']];
    }

    public function messages(): array
    {
        return ['alasan.min' => 'Jelaskan alasan laporan minimal 10 karakter.'];
    }
}

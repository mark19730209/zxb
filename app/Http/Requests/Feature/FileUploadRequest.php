<?php

namespace App\Http\Requests\Feature;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'max:2048'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'max:5120'],
        ];
    }
}

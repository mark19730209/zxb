<?php

namespace App\Http\Requests\Feature;

use Illuminate\Foundation\Http\FormRequest;

class SimpleFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'string', 'in:developer,designer,manager,other'],
            'subscribe' => ['boolean'],
        ];
    }
}

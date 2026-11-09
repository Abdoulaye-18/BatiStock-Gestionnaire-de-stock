<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LivreurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:150'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'disponible' => ['required', 'boolean'],
        ];
    }
}
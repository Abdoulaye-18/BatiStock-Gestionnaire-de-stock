<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntrepotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('entrepots', 'code')->ignore($this->route('entrepot')?->id)],
            'nom' => ['required', 'string', 'max:100'],
            'localisation' => ['nullable', 'string', 'max:150'],
        ];
    }
}
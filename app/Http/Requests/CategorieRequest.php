<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('categories', 'code')->ignore($this->route('categorie')?->id)],
            'nom' => ['required', 'string', 'max:100'],
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('reference')) {
            $this->merge(['reference' => strtoupper($this->reference)]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('article')?->id;

        return [
            'reference' => ['required', 'string', 'max:50', Rule::unique('articles', 'reference')->ignore($id)],
            'nom' => ['required', 'string', 'max:150'],
            'categorie_id' => ['required', 'exists:categories,id'],
            'unite_id' => ['required', 'exists:unites,id'],
            'prix_vente' => ['nullable', 'numeric', 'min:0'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
        ];
    }
}
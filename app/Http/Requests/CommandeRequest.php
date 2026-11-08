<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'adresse_destination' => ['required', 'string', 'max:255'],
            'date_commande' => ['nullable', 'date', 'before_or_equal:today'],
            'lignes' => ['required', 'array', 'min:1', 'max:30'],
            'lignes.*.article_id' => [
                'required', 'distinct',
                Rule::exists('articles', 'id')->where('actif', true),
            ],
            'lignes.*.quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'lignes.*.prix_unitaire' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return ['lignes.*.article_id.distinct' => 'Un article ne peut apparaître qu\'une seule fois.'];
    }
}
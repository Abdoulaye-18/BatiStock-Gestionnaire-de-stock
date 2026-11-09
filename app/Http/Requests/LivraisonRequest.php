<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    public function rules(): array
    {
        return [
            'source_type' => ['required', Rule::in(['commande', 'demande'])],
            'source_id' => ['required', 'integer'],
            'livreur_id' => ['required', Rule::exists('livreurs', 'id')->where('disponible', true)],
            'adresse_destination' => ['required', 'string', 'max:255'],
            'date_prevue' => ['required', 'date', 'after_or_equal:today'],
            'observation' => ['nullable', 'string', 'max:500'],
            'lignes' => ['required', 'array', 'min:1', 'max:30'],
            'lignes.*.article_id' => ['required', 'distinct', 'exists:articles,id'],
            'lignes.*.entrepot_id' => ['required', 'exists:entrepots,id'],
            'lignes.*.qte_prevue' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'livreur_id.exists' => 'Livreur invalide ou indisponible.',
            'lignes.*.article_id.distinct' => 'Un article ne peut apparaître qu\'une seule fois.',
        ];
    }
}
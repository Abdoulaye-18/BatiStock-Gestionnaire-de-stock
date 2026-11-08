<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'chantier');
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            // Un responsable ne peut demander que pour ses propres chantiers actifs
            'chantier_id' => ['required', Rule::exists('chantiers', 'id')->where(function ($q) use ($user) {
                $q->where('etat', 'actif');
                if ($user->hasRole('chantier')) {
                    $q->where('responsable_id', $user->id);
                }
            })],
            'motif' => ['required', 'string', 'max:255'],
            'date_souhaitee' => ['nullable', 'date', 'after_or_equal:today'],
            'lignes' => ['required', 'array', 'min:1', 'max:30'],
            'lignes.*.article_id' => [
                'required', 'distinct',
                Rule::exists('articles', 'id')->where('actif', true),
            ],
            'lignes.*.quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'chantier_id.exists' => 'Chantier invalide : il doit être actif et vous être attribué.',
            'lignes.*.article_id.distinct' => 'Un article ne peut apparaître qu\'une seule fois.',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmationLivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    public function rules(): array
    {
        return [
            'observation' => ['nullable', 'string', 'max:500'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.id' => ['required', 'integer'],
            'lignes.*.qte_livree' => ['required', 'integer', 'min:0', 'max:1000000'],
            'lignes.*.motif_ecart' => ['nullable', 'string', 'max:255'],
        ];
    }
}
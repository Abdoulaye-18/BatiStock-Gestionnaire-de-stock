<?php

namespace App\Http\Requests;

class EntreeRequest extends MouvementRequest
{
    public function rules(): array
    {
        return $this->communes() + [
            'quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'fournisseur_id' => ['nullable', 'exists:fournisseurs,id'],
        ];
    }
}
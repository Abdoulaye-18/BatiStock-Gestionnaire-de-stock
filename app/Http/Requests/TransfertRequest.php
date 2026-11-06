<?php

namespace App\Http\Requests;

class TransfertRequest extends MouvementRequest
{
    public function rules(): array
    {
        return $this->communes() + [
            'quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
            'entrepot_destination_id' => ['required', 'exists:entrepots,id', 'different:entrepot_id'],
        ];
    }
}
<?php

namespace App\Http\Requests;

class AjustementRequest extends MouvementRequest
{
    public function rules(): array
    {
        return $this->communes() + [
            'quantite' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
        ];
    }
}
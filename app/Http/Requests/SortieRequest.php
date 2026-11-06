<?php

namespace App\Http\Requests;

class SortieRequest extends MouvementRequest
{
    public function rules(): array
    {
        return $this->communes() + [
            'quantite' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
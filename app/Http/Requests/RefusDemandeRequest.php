<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefusDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'controle');
    }

    public function rules(): array
    {
        return ['motif_refus' => ['required', 'string', 'max:255']];
    }
}
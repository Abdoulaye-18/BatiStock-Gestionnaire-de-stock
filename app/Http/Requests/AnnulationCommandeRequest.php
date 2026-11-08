<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnulationCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    public function rules(): array
    {
        return ['motif_annulation' => ['required', 'string', 'max:255']];
    }
}
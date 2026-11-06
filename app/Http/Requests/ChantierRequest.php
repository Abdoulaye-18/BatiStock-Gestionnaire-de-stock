<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChantierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user->hasRole('admin', 'gestionnaire')) {
            return true;
        }

        if (! $user->hasRole('chantier')) {
            return false;
        }

        // Un responsable ne modifie que ses propres chantiers
        $chantier = $this->route('chantier');

        return ! $chantier || $chantier->responsable_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:150'],
            'localisation' => ['nullable', 'string', 'max:150'],
            'etat' => ['required', Rule::in(['actif', 'termine'])],
            'responsable_id' => ['nullable', Rule::exists('users', 'id')->where('actif', true)],
        ];
    }
}
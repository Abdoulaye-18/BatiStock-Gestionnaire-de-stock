<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class MouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'gestionnaire');
    }

    protected function communes(): array
    {
        return [
            'article_id' => ['required', Rule::exists('articles', 'id')->where('actif', true)],
            'entrepot_id' => ['required', 'exists:entrepots,id'],
            'motif' => ['required', 'string', 'max:255'],
            'date_mouvement' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}